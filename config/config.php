<?php
/**
 * Carga la configuracion desde el archivo .env y provee helpers globales.
 */

define('ROOT_PATH',    dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

$envFile = ROOT_PATH . '/.env';

// Si no existe .env, copiar desde .env.example
if (!file_exists($envFile) && file_exists(ROOT_PATH . '/.env.example')) {
    copy(ROOT_PATH . '/.env.example', $envFile);
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Omitir comentarios y lineas vacias
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Remover comillas envolventes
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Obtiene el valor de una variable de entorno.
 *
 * @param  string $key
 * @param  mixed  $default
 * @return mixed
 */
function env(string $key, $default = null)
{
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    return $val;
}

/**
 * Obtiene una variable de entorno y la convierte a booleano.
 *
 * @param  string $key
 * @param  bool   $default
 * @return bool
 */
function envBool(string $key, bool $default = false): bool
{
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null) {
        return $default;
    }
    return in_array(strtolower((string) $val), ['true', '1', 'yes', 'on'], true);
}

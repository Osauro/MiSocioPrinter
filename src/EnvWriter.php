<?php

namespace MiSocio;

/**
 * Permite leer y actualizar variables en el archivo .env
 * de forma segura sin perder comentarios ni estructura.
 */
class EnvWriter
{
    private string $filePath;

    public function __construct(string $filePath = '')
    {
        $this->filePath = $filePath !== '' ? $filePath : ROOT_PATH . '/.env';
    }

    /**
     * Actualiza multiples claves en el .env.
     * Si la clave no existe la agrega al final.
     *
     * @param  array<string, mixed> $values
     * @throws \RuntimeException si no puede escribir el archivo
     */
    public function update(array $values): void
    {
        if (!file_exists($this->filePath)) {
            $this->create($values);
            return;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new \RuntimeException('No se puede leer el archivo .env');
        }

        foreach ($values as $rawKey => $rawValue) {
            $key          = $this->sanitizeKey($rawKey);
            $escapedValue = $this->escapeValue((string) $rawValue);

            // Reemplaza la linea existente si la clave ya esta
            $pattern = '/^(' . preg_quote($key, '/') . '\s*=).*$/m';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "$1{$escapedValue}", $content);
            } else {
                // Agregar al final
                $content = rtrim($content) . "\n{$key}={$escapedValue}\n";
            }
        }

        if (file_put_contents($this->filePath, $content) === false) {
            throw new \RuntimeException('No se puede escribir el archivo .env');
        }

        // Actualizar $_ENV en memoria para que surta efecto inmediato
        foreach ($values as $rawKey => $rawValue) {
            $key = $this->sanitizeKey($rawKey);
            $_ENV[$key] = (string) $rawValue;
            putenv("{$key}={$rawValue}");
        }
    }

    /**
     * Crea un nuevo archivo .env desde cero.
     */
    private function create(array $values): void
    {
        $lines = [];
        foreach ($values as $rawKey => $rawValue) {
            $key    = $this->sanitizeKey($rawKey);
            $lines[] = "{$key}=" . $this->escapeValue((string) $rawValue);
        }

        if (file_put_contents($this->filePath, implode("\n", $lines) . "\n") === false) {
            throw new \RuntimeException('No se puede crear el archivo .env');
        }
    }

    /**
     * Sanea el nombre de la clave (solo letras, numeros y guion bajo, mayusculas).
     */
    private function sanitizeKey(string $key): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $key));
    }

    /**
     * Escapa un valor para el archivo .env.
     * Pone comillas dobles si el valor contiene espacios, # o comillas.
     */
    private function escapeValue(string $value): string
    {
        if ($value === '') {
            return '';
        }
        // Necesita comillas
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }
        return $value;
    }
}

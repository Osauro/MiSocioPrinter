<?php
/**
 * MiSocio Printer - Endpoint de impresion
 *
 * URL limpia (requiere mod_rewrite / .htaccess):
 *   /venta/123
 *   /prestamo/45
 *
 * URL directa (fallback sin mod_rewrite):
 *   print.php?tipo=venta&id=123
 *
 * Retorna JSON:
 *   { "success": true,  "message": "Venta #123 impresa correctamente" }
 *   { "success": false, "message": "Descripcion del error" }
 */

// Capturar TODO output (warnings/notices/errors de PHP) antes de escribir JSON
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/config/config.php';

// Verificar que las dependencias esten instaladas
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Dependencias no instaladas. Ejecuta: composer install',
    ]);
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use MiSocio\Tickets\VentaTicket;
use MiSocio\Tickets\PrestamoTicket;

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Descartar cualquier output previo (warnings/notices de PHP)
ob_end_clean();
ob_start(); // nuevo buffer limpio para la respuesta JSON

// ── Resolver parametros: querystring o PATH_INFO (/venta/123) ────────────────

$tipo = trim($_GET['tipo'] ?? '');
$id   = (int) ($_GET['id']   ?? 0);

// Fallback: resolver desde URI si no vienen por querystring (URL limpia sin .htaccess)
if ($tipo === '' || $id <= 0) {
    $uri      = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $path     = trim((string) $uri, '/');
    // Quitar base del directorio si aplica (e.g. misocio-printer/venta/5)
    $path     = preg_replace('#^.*misocio-printer/#', '', $path);
    $segments = array_values(array_filter(explode('/', $path)));
    if (count($segments) >= 2) {
        $tipo = $segments[0];
        $id   = (int) $segments[1];
    }
}

$tiposPermitidos = ['venta', 'prestamo'];

if (!in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parametro "tipo" invalido. Valores permitidos: ' . implode(', ', $tiposPermitidos),
    ]);
    exit;
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parametro "id" debe ser un entero positivo.',
    ]);
    exit;
}

// ── Imprimir ─────────────────────────────────────────────────────────────────

try {
    switch ($tipo) {
        case 'venta':
            $ticket = new VentaTicket();
            break;
        case 'prestamo':
            $ticket = new PrestamoTicket();
            break;
    }

    $ticket->print($id);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($tipo) . " #$id impreso correctamente.",
        'tipo'    => $tipo,
        'id'      => $id,
    ]);

} catch (\RuntimeException $e) {
    // Registro no encontrado
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'tipo'    => $tipo,
        'id'      => $id,
    ]);
} catch (\Exception $e) {
    // Error de impresion u otro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al imprimir: ' . $e->getMessage(),
        'tipo'    => $tipo,
        'id'      => $id,
    ]);
}

<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/config/config.php';

if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=UTF-8');
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Dependencias no instaladas. Ejecuta: composer install']);
    exit;
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

use MiSocio\Tickets\TestTicket;

ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

try {
    $ticket = new TestTicket();
    $ticket->print();
    echo json_encode(['success' => true, 'message' => 'Prueba enviada a la impresora correctamente']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()]);
}

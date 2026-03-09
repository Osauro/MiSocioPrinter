<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use MiSocio\PrinterManager;

ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

try {
    $printers       = PrinterManager::detectPrinters();
    $currentPrinter = env('PRINTER_NAME', '');

    echo json_encode([
        'success'  => true,
        'printers' => $printers,
        'count'    => count($printers),
        'current'  => $currentPrinter,
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

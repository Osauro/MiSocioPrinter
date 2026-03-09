<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use MiSocio\Database;

ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Resetear singleton por si se acaban de cambiar las credenciales
Database::reset();

$result = Database::test();

if (!$result['success']) {
    http_response_code(500);
}

echo json_encode($result);

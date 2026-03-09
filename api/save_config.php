<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use MiSocio\EnvWriter;

ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

// Aceptar JSON o form-data
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Lista blanca de claves permitidas
$allowed = [
    'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
    'PRINTER_NAME', 'PRINTER_TYPE', 'PRINTER_HOST', 'PRINTER_PORT',
    'PAPER_WIDTH',
    'COMPANY_NAME', 'COMPANY_LOGO', 'FOOTER_MESSAGE', 'CONTACT_INFO',
    'SHOW_LOGO', 'SHOW_QR', 'AUTO_CUT', 'SOUND_ALERT',
    'LOGO_IMAGE',
];

$data = [];
foreach ($allowed as $key) {
    if (array_key_exists($key, $input)) {
        // Sanitizar: solo cadenas, sin nulos
        $data[$key] = (string)$input[$key];
    }
}

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos validos']);
    exit;
}

try {
    $writer = new EnvWriter();
    $writer->update($data);
    echo json_encode(['success' => true, 'message' => 'Configuracion guardada correctamente']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
}

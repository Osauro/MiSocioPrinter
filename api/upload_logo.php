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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$uploadError = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;

if ($uploadError !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el limite de upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el limite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subio de forma parcial.',
        UPLOAD_ERR_NO_FILE    => 'No se selecciono ningun archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se puede escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extension de PHP detuvo la subida.',
    ];
    echo json_encode([
        'success' => false,
        'message' => $messages[$uploadError] ?? 'Error desconocido al subir el archivo.',
    ]);
    exit;
}

$file    = $_FILES['logo'];
$maxSize = 2 * 1024 * 1024; // 2 MB

// Validar tipo MIME real (no confiar en $_FILES['type'])
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowed = ['image/png' => 'png', 'image/gif' => 'gif', 'image/bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp'];

if (!array_key_exists($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no soportado. Use PNG, GIF o BMP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'El archivo supera el limite de 2 MB.']);
    exit;
}

$uploadDir = ROOT_PATH . '/';

// Eliminar logos anteriores en la raíz
foreach (glob($uploadDir . 'logo.*') as $oldFile) {
    if (basename($oldFile) !== '.htaccess') {
        unlink($oldFile);
    }
}

$ext         = $allowed[$mimeType];
$newFilename = 'logo.' . $ext;
$destination = $uploadDir . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo.']);
    exit;
}

// Actualizar .env
try {
    $writer = new EnvWriter();
    $writer->update(['LOGO_IMAGE' => $newFilename]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Logo guardado pero no se actualizo .env: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Logo cargado correctamente.',
    'filename' => $newFilename,
]);

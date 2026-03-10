<?php
/**
 * Script de prueba para verificar el procesamiento del logo
 */

require_once __DIR__ . '/config/config.php';

echo "=== TEST DE LOGO ===\n\n";

// Verificar que GD está habilitado
if (!extension_loaded('gd')) {
    echo "ERROR: La extensión GD no está cargada\n";
    exit(1);
}
echo "✓ GD está habilitado\n";

// Obtener la ruta del logo desde .env
$logoImage = env('LOGO_IMAGE', '');
echo "Logo configurado en .env: " . ($logoImage ?: '(vacío)') . "\n";

if (empty($logoImage)) {
    echo "ERROR: LOGO_IMAGE no está configurado en .env\n";
    exit(1);
}

// Construir ruta completa
$logoPath = UPLOADS_PATH . '/' . basename($logoImage);
echo "Ruta completa del logo: $logoPath\n";

// Verificar que existe
if (!file_exists($logoPath)) {
    echo "ERROR: El archivo no existe: $logoPath\n";
    exit(1);
}
echo "✓ El archivo existe\n";

// Obtener información de la imagen
$imageInfo = @getimagesize($logoPath);
if (!$imageInfo) {
    echo "ERROR: No se pudo obtener información de la imagen\n";
    exit(1);
}

echo "Información de la imagen:\n";
echo "  - Ancho: " . $imageInfo[0] . "px\n";
echo "  - Alto: " . $imageInfo[1] . "px\n";
echo "  - Tipo: " . image_type_to_mime_type($imageInfo[2]) . "\n";
echo "✓ La imagen es válida\n\n";

// Intentar cargarla con GD
echo "Intentando cargar con GD...\n";
$src = null;
switch ($imageInfo[2]) {
    case IMAGETYPE_PNG:
        echo "Tipo: PNG\n";
        $src = @imagecreatefrompng($logoPath);
        break;
    case IMAGETYPE_JPEG:
        echo "Tipo: JPEG\n";
        $src = @imagecreatefromjpeg($logoPath);
        break;
    case IMAGETYPE_GIF:
        echo "Tipo: GIF\n";
        $src = @imagecreatefromgif($logoPath);
        break;
    default:
        echo "ERROR: Tipo de imagen no soportado: " . $imageInfo[2] . "\n";
        exit(1);
}

if (!$src) {
    echo "ERROR: No se pudo crear imagen con GD\n";
    exit(1);
}
echo "✓ Imagen cargada correctamente con GD\n";

// Crear versión procesada
echo "\nProcesando imagen...\n";
$maxWidth = 300;
$origW = imagesx($src);
$origH = imagesy($src);
$scale = ($origW > $maxWidth) ? $maxWidth / $origW : 1.0;
$newW  = max(1, (int)($origW * $scale));
$newH  = max(1, (int)($origH * $scale));

echo "Dimensiones originales: {$origW}x{$origH}\n";
echo "Dimensiones nuevas: {$newW}x{$newH}\n";

$dst = imagecreatetruecolor($newW, $newH);
if (!$dst) {
    echo "ERROR: No se pudo crear imagen destino\n";
    imagedestroy($src);
    exit(1);
}

$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

$tmpPath = sys_get_temp_dir() . '/misocio_logo_test.png';
$result = imagepng($dst, $tmpPath, 0);
imagedestroy($dst);

if (!$result) {
    echo "ERROR: No se pudo guardar imagen temporal\n";
    exit(1);
}

echo "✓ Imagen procesada correctamente\n";
echo "Ruta temporal: $tmpPath\n";
echo "Tamaño del archivo: " . filesize($tmpPath) . " bytes\n\n";

// Intentar cargar con escpos-php
echo "Probando con escpos-php...\n";
require_once __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\EscposImage;

try {
    $image = EscposImage::load($tmpPath, false);
    echo "✓ Imagen cargada correctamente con EscposImage\n";
    echo "Dimensiones finales: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
} catch (Exception $e) {
    echo "ERROR al cargar con EscposImage: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ✓ TODOS LOS TESTS PASARON ===\n";

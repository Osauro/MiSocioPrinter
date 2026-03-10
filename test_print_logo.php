<?php
/**
 * Prueba de impresión del logo solamente
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "=== PRUEBA DE IMPRESIÓN DE LOGO ===\n\n";

// Obtener configuración
$printerName = env('PRINTER_NAME', '');
$logoImage = env('LOGO_IMAGE', '');

if (empty($printerName)) {
    echo "ERROR: PRINTER_NAME no configurado\n";
    exit(1);
}

if (empty($logoImage)) {
    echo "ERROR: LOGO_IMAGE no configurado\n";
    exit(1);
}

echo "Impresora: $printerName\n";
echo "Logo: $logoImage\n\n";

// Preparar ruta del logo
$logoPath = UPLOADS_PATH . '/' . basename($logoImage);
if (!file_exists($logoPath)) {
    echo "ERROR: Logo no existe: $logoPath\n";
    exit(1);
}

echo "Procesando imagen...\n";

// Procesar imagen (igual que en BaseTicket)
$maxWidth = 300;
$imageInfo = @getimagesize($logoPath);
if (!$imageInfo) {
    echo "ERROR: No se pudo obtener info de imagen\n";
    exit(1);
}

$src = null;
switch ($imageInfo[2]) {
    case IMAGETYPE_PNG:
        $src = @imagecreatefrompng($logoPath);
        break;
    case IMAGETYPE_JPEG:
        $src = @imagecreatefromjpeg($logoPath);
        break;
    case IMAGETYPE_GIF:
        $src = @imagecreatefromgif($logoPath);
        break;
}

if (!$src) {
    echo "ERROR: No se pudo cargar imagen\n";
    exit(1);
}

$origW = imagesx($src);
$origH = imagesy($src);
$scale = ($origW > $maxWidth) ? $maxWidth / $origW : 1.0;
$newW  = max(1, (int)($origW * $scale));
$newH  = max(1, (int)($origH * $scale));

echo "Redimensionando de {$origW}x{$origH} a {$newW}x{$newH}...\n";

$dst   = imagecreatetruecolor($newW, $newH);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

$tmpPath = sys_get_temp_dir() . '/misocio_logo_print.png';
imagepng($dst, $tmpPath, 0);
imagedestroy($dst);

echo "Imagen temporal: $tmpPath\n";
echo "Tamaño: " . filesize($tmpPath) . " bytes\n\n";

// Inicializar impresora
echo "Conectando a impresora...\n";
try {
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);
    $printer->initialize();
    
    echo "✓ Conectado\n\n";
    
    // Intentar diferentes métodos de impresión
    echo "Método 1: EscposImage + bitImage\n";
    try {
        $image = EscposImage::load($tmpPath, false);
        echo "  - Imagen cargada (dimensiones: " . $image->getWidth() . "x" . $image->getHeight() . ")\n";
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->bitImage($image);
        $printer->feed(2);
        echo "  ✓ Impreso con bitImage\n\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
    }
    
    echo "Método 2: EscposImage + graphics\n";
    try {
        $image = EscposImage::load($tmpPath, false);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->graphics($image);
        $printer->feed(2);
        echo "  ✓ Impreso con graphics\n\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
    }
    
    // Texto de prueba
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("=== FIN DE PRUEBA ===\n");
    $printer->feed(3);
    $printer->cut();
    
    $printer->close();
    echo "\n✓ Impresión completada\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

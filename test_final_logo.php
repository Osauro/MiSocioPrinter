<?php
/**
 * Test final de impresión con el fix aplicado
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "=== TEST FINAL DE IMPRESIÓN ===\n\n";

$printerName = env('PRINTER_NAME', '');
$logoImage = env('LOGO_IMAGE', '');
$logoPath = UPLOADS_PATH . '/' . basename($logoImage);

echo "Impresora: $printerName\n";
echo "Logo: $logoPath\n\n";

// Procesar imagen
echo "Procesando imagen...\n";
$src = imagecreatefrompng($logoPath);
$origW = imagesx($src);
$origH = imagesy($src);
$maxWidth = 200;
$scale = ($origW > $maxWidth) ? $maxWidth / $origW : 1.0;
$newW  = max(1, (int)($origW * $scale));
$newH  = max(1, (int)($origH * $scale));

$dst = imagecreatetruecolor($newW, $newH);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $newW - 1, $newH - 1, $white);
imagealphablending($dst, false);
imagesavealpha($dst, false);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

$tmpPath = sys_get_temp_dir() . '/logo_final_test.png';
imagepng($dst, $tmpPath, 9);
imagedestroy($dst);

echo "Imagen procesada: {$newW}x{$newH}\n";
echo "Archivo temporal: $tmpPath\n\n";

// Imprimir
try {
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);
    $printer->initialize();
    
    echo "Conectado a la impresora\n";
    
    // Cargar imagen con allowOptimisations = false
    $image = EscposImage::load($tmpPath, false);
    
    // Forzar carga
    $image->toRasterFormat();
    
    echo "Dimensiones de la imagen: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
    
    if ($image->getWidth() > 0 && $image->getHeight() > 0) {
        // Imprimir encabezado
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(1, 2);
        $printer->text("TEST DE LOGO\n");
        $printer->setTextSize(1, 1);
        $printer->text("-----------------------------------\n\n");
        
        // Imprimir logo
        $printer->bitImage($image);
        $printer->feed(1);
        
        $printer->text("\n");
        $printer->setEmphasis(true);
        $printer->text("Logo impreso correctamente\n");
        $printer->setEmphasis(false);
        $printer->text(date('Y-m-d H:i:s') . "\n");
        
        $printer->feed(3);
        $printer->cut();
        $printer->close();
        
        echo "\n✓✓✓ IMPRESIÓN COMPLETADA\n";
        echo "Revisa el ticket físico para confirmar que el logo aparece.\n";
    } else {
        echo "\nXXX ERROR: Dimensiones invalidas\n";
        $printer->close();
    }
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}

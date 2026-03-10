<?php
/**
 * Test final con logo en raíz
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "=== TEST DE IMPRESIÓN CON LOGO EN RAÍZ ===\n\n";

$printerName = env('PRINTER_NAME', '');
$logoImage = env('LOGO_IMAGE', '');

// Ahora busca en ROOT_PATH
$logoPath = ROOT_PATH . '/' . basename($logoImage);

echo "Impresora: $printerName\n";
echo "Logo configurado: $logoImage\n";
echo "Ruta completa: $logoPath\n\n";

if (!file_exists($logoPath)) {
    echo "❌ ERROR: El logo no existe en la ruta: $logoPath\n";
    exit(1);
}

echo "✓ Logo encontrado\n";
echo "Tamaño: " . filesize($logoPath) . " bytes\n";

$imageInfo = getimagesize($logoPath);
echo "Dimensiones: {$imageInfo[0]}x{$imageInfo[1]}\n\n";

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

$tmpPath = sys_get_temp_dir() . '/logo_root_test.png';
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
    
    $image = EscposImage::load($tmpPath, false);
    $image->toRasterFormat();
    
    echo "Dimensiones de la imagen: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
    
    if ($image->getWidth() > 0 && $image->getHeight() > 0) {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(1, 2);
        $printer->text("LOGO DESDE RAIZ\n");
        $printer->setTextSize(1, 1);
        $printer->text("-----------------------------------\n\n");
        
        $printer->bitImage($image);
        $printer->feed(1);
        
        $printer->text("\n");
        $printer->setEmphasis(true);
        $printer->text("Logo impreso correctamente\n");
        $printer->setEmphasis(false);
        $printer->text("Ubicacion: raiz del proyecto\n");
        $printer->text(date('Y-m-d H:i:s') . "\n");
        
        $printer->feed(3);
        $printer->cut();
        $printer->close();
        
        echo "\n✓✓✓ IMPRESIÓN COMPLETADA EXITOSAMENTE\n";
        echo "Revisa el ticket físico para confirmar que el logo aparece.\n";
    } else {
        echo "\n❌ ERROR: Dimensiones inválidas\n";
        $printer->close();
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

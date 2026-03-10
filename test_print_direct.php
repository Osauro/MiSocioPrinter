<?php
/**
 * Prueba de impresión del logo SIN pre-procesamiento
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "=== PRUEBA DE IMPRESIÓN DIRECTA ===\n\n";

$printerName = env('PRINTER_NAME', '');
$logoImage = env('LOGO_IMAGE', '');

$logoPath = UPLOADS_PATH . '/' . basename($logoImage);
echo "Logo: $logoPath\n";

if (!file_exists($logoPath)) {
    echo "ERROR: Logo no existe\n";
    exit(1);
}

try {
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);
    $printer->initialize();
    
    echo "Conectado a $printerName\n\n";
    
    // Método 1: Cargar imagen directamente
    echo "=== Test 1: Imagen directa ===\n";
    try {
        $image = EscposImage::load($logoPath);
        echo "Dimensiones: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
        if ($image->getWidth() > 0) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->graphics($image);
            $printer->text("Imagen directa\n");
            $printer->feed(2);
            echo "✓ Impreso\n\n";
        } else {
            echo "X Dimensiones inválidas\n\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
    
    // Método 2: Con redimensionamiento pequeño (128px)
    echo "=== Test 2: Redimensionado a 128px ===\n";
    try {
        $src = imagecreatefrompng($logoPath);
        $origW = imagesx($src);
        $origH = imagesy($src);
        
        $maxW = 128;
        $scale = $maxW / $origW;
        $newW = (int)($origW * $scale);
        $newH = (int)($origH * $scale);
        
        echo "Redimensionando de {$origW}x{$origH} a {$newW}x{$newH}\n";
        
        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW - 1, $newH - 1, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        
        $tmpPath = sys_get_temp_dir() . '/logo_128.png';
        imagepng($dst, $tmpPath);
        imagedestroy($src);
        imagedestroy($dst);
        
        echo "Guardar en: $tmpPath (" . filesize($tmpPath) . " bytes)\n";
        
        $image = EscposImage::load($tmpPath);
        echo "Dimensiones EscPos: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
        
        if ($image->getWidth() > 0) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->graphics($image);
            $printer->text("Redimensionado 128px\n");
            $printer->feed(2);
            echo "✓ Impreso\n\n";
        } else {
            echo "X Dimensiones inválidas\n\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
    
    // Método 3: Con redimensionamiento mediano (256px)
    echo "=== Test 3: Redimensionado a 256px ===\n";
    try {
        $src = imagecreatefrompng($logoPath);
        $origW = imagesx($src);
        $origH = imagesy($src);
        
        $maxW = 256;
        $scale = $maxW / $origW;
        $newW = (int)($origW * $scale);
        $newH = (int)($origH * $scale);
        
        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW - 1, $newH - 1, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        
        $tmpPath = sys_get_temp_dir() . '/logo_256.png';
        imagepng($dst, $tmpPath);
        imagedestroy($src);
        imagedestroy($dst);
        
        $image = EscposImage::load($tmpPath);
        echo "Dimensiones: " . $image->getWidth() . "x" . $image->getHeight() . "\n";
        
        if ($image->getWidth() > 0) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->graphics($image);
            $printer->text("Redimensionado 256px\n");
            $printer->feed(2);
            echo "✓ Impreso\n\n";
        } else {
            echo "X Dimensiones inválidas\n\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
    
    $printer->text("\n=== FIN ===\n");
    $printer->feed(3);
    $printer->cut();
    $printer->close();
    
    echo "\n✓ Completado. Revisa el ticket impreso.\n";
    
} catch (Exception $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
    exit(1);
}

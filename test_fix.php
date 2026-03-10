<?php
/**
 * Test con allowOptimisations = false
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\EscposImage;

$logoPath = UPLOADS_PATH . '/logo.png';

echo "=== TEST CON allowOptimisations = false ===\n\n";

if (!file_exists($logoPath)) {
    echo "ERROR: Logo no existe\n";
    exit(1);
}

// Procesar imagen como en BaseTicket
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

$tmpPath = sys_get_temp_dir() . '/logo_test_' . uniqid() . '.png';
imagepng($dst, $tmpPath, 9);
imagedestroy($dst);

echo "Imagen procesada: {$newW}x{$newH}\n";
echo "Guardada en: $tmpPath\n";
echo "Tamaño: " . filesize($tmpPath) . " bytes\n\n";

// Cargar con EscposImage SIN optimizaciones
echo "Cargando con allowOptimisations = false...\n";
$image = EscposImage::load($tmpPath, false);

echo "ANTES de toRasterFormat():\n";
echo "  Width: " . $image->getWidth() . "\n";
echo "  Height: " . $image->getHeight() . "\n\n";

// Forzar la carga llamando a toRasterFormat()
echo "Llamando a toRasterFormat()...\n";
$raster = $image->toRasterFormat();
echo "Raster generado: " . strlen($raster) . " bytes\n\n";

echo "DESPUÉS de toRasterFormat():\n";
echo "  Width: " . $image->getWidth() . "\n";
echo "  Height: " . $image->getHeight() . "\n\n";

if ($image->getWidth() > 0 && $image->getHeight() > 0) {
    echo "✓✓✓ ¡ÉXITO! La imagen tiene dimensiones válidas\n";
} else {
    echo "XXX FALLO: Las dimensiones siguen en 0\n";
}

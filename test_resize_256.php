<?php
/**
 * Test de redimensionamiento a 256x256
 */

require_once __DIR__ . '/config/config.php';

echo "=== TEST DE REDIMENSIONAMIENTO 256x256 ===\n\n";

// Verificar que existe el logo actual
$logoPath = ROOT_PATH . '/logo.png';

if (!file_exists($logoPath)) {
    echo "❌ No existe logo.png para probar\n";
    exit(1);
}

echo "Logo actual:\n";
$info = getimagesize($logoPath);
echo "  - Dimensiones: {$info[0]}x{$info[1]}\n";
echo "  - Tipo: {$info['mime']}\n";
echo "  - Tamaño: " . filesize($logoPath) . " bytes\n\n";

// Simular el redimensionamiento
echo "Redimensionando a 256x256...\n";

$src = imagecreatefrompng($logoPath);
if (!$src) {
    echo "❌ Error al cargar imagen\n";
    exit(1);
}

// Crear imagen de 256x256 con fondo blanco
$dst = imagecreatetruecolor(256, 256);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, 255, 255, $white);

// Redimensionar manteniendo proporción y centrando
$origW = imagesx($src);
$origH = imagesy($src);

echo "  - Dimensiones originales: {$origW}x{$origH}\n";

$scale = min(256 / $origW, 256 / $origH);
$newW = (int)($origW * $scale);
$newH = (int)($origH * $scale);

echo "  - Escala calculada: $scale\n";
echo "  - Nuevas dimensiones: {$newW}x{$newH}\n";

$offsetX = (int)((256 - $newW) / 2);
$offsetY = (int)((256 - $newH) / 2);

echo "  - Offset: X=$offsetX, Y=$offsetY\n";

imagecopyresampled($dst, $src, $offsetX, $offsetY, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

// Guardar como test
$testPath = ROOT_PATH . '/logo_256_test.png';
imagepng($dst, $testPath, 9);
imagedestroy($dst);

echo "\n✓ Imagen redimensionada guardada en: logo_256_test.png\n";

// Verificar resultado
$infoTest = getimagesize($testPath);
echo "\nResultado:\n";
echo "  - Dimensiones: {$infoTest[0]}x{$infoTest[1]}\n";
echo "  - Tipo: {$infoTest['mime']}\n";
echo "  - Tamaño: " . filesize($testPath) . " bytes\n";

if ($infoTest[0] === 256 && $infoTest[1] === 256) {
    echo "\n✓✓✓ ÉXITO: La imagen es exactamente 256x256\n";
} else {
    echo "\n❌ ERROR: Las dimensiones no son 256x256\n";
}

echo "\nPrueba subir una nueva imagen desde http://localhost:5421/\n";
echo "para verificar que el redimensionamiento funciona en tiempo real.\n";

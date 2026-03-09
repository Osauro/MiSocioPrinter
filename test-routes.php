<?php
// Archivo simple de test - sin incluir config
header('Content-Type: text/plain; charset=UTF-8');

echo "=== TEST DE RUTAS ===\n\n";

echo "Este archivo: " . __FILE__ . "\n";
echo "Directorio: " . __DIR__ . "\n\n";

echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n\n";

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$appBase = $scriptDir === '/' ? '' : $scriptDir;

echo "scriptDir calculado: '$scriptDir'\n";
echo "appBase calculado: '$appBase'\n\n";

// Verificar logo
$logoPath = __DIR__ . '/uploads/logo.png';
echo "Ruta logo: $logoPath\n";
echo "Logo existe: " . (file_exists($logoPath) ? 'SI' : 'NO') . "\n";

if (file_exists($logoPath)) {
    echo "Tamaño: " . filesize($logoPath) . " bytes\n";
}

echo "\n=== FIN TEST ===";

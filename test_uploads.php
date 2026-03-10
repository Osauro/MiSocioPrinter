<?php
/**
 * Test de acceso a uploads
 */

$uploadsPath = __DIR__ . '/uploads';
$logoPath = $uploadsPath . '/logo.png';

echo "<h2>Test de Acceso a Uploads</h2>";

// Verificar que la carpeta existe
if (!is_dir($uploadsPath)) {
    echo "<p style='color:red'>❌ La carpeta uploads no existe</p>";
    exit;
}
echo "<p style='color:green'>✓ La carpeta uploads existe</p>";

// Verificar que el logo existe
if (!file_exists($logoPath)) {
    echo "<p style='color:red'>❌ El archivo logo.png no existe</p>";
    exit;
}
echo "<p style='color:green'>✓ El archivo logo.png existe</p>";

// Verificar permisos de lectura
if (!is_readable($logoPath)) {
    echo "<p style='color:red'>❌ El archivo logo.png no es legible</p>";
    exit;
}
echo "<p style='color:green'>✓ El archivo logo.png es legible</p>";

// Obtener información del archivo
$fileInfo = stat($logoPath);
$fileSize = filesize($logoPath);
$imageInfo = getimagesize($logoPath);

echo "<h3>Información del archivo:</h3>";
echo "<ul>";
echo "<li>Ruta: $logoPath</li>";
echo "<li>Tamaño: " . number_format($fileSize) . " bytes (" . round($fileSize/1024, 2) . " KB)</li>";
echo "<li>Dimensiones: {$imageInfo[0]}x{$imageInfo[1]}</li>";
echo "<li>Tipo MIME: {$imageInfo['mime']}</li>";
echo "</ul>";

// Mostrar la imagen
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$logoUrl = $baseUrl . '/uploads/logo.png';

echo "<h3>Vista previa:</h3>";
echo "<p>URL: <a href='$logoUrl' target='_blank'>$logoUrl</a></p>";
echo "<img src='$logoUrl' alt='Logo' style='max-width:300px; border:1px solid #ccc; padding:10px;' onerror=\"this.style.border='2px solid red'; this.alt='❌ Error al cargar'\">";

echo "<hr>";
echo "<h3>Acceso directo:</h3>";
echo "<p>Intenta acceder directamente a: <a href='$logoUrl' target='_blank'>$logoUrl</a></p>";
?>

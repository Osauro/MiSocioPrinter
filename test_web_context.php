<?php
/**
 * Test simulando ejecución desde servidor web
 * (cambio de directorio para simular context web)
 */

// Cambiar el directorio de trabajo como lo haría Apache
chdir(sys_get_temp_dir());

echo "=== TEST: SIMULACIÓN DESDE SERVIDOR WEB ===\n\n";
echo "Directorio de trabajo actual: " . getcwd() . "\n";
echo "Este es el problema: PHP no está en la raíz del proyecto\n\n";

require_once 'C:/laragon/www/misocio-printer/vendor/autoload.php';
require_once 'C:/laragon/www/misocio-printer/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "1. Verificando ruta relativa 'logo.png':\n";
if (file_exists('logo.png')) {
    echo "   ✓ Encontrado (NO ESPERADO)\n";
} else {
    echo "   ✗ NO encontrado (ESPERADO - este era el problema)\n";
}

echo "\n2. Verificando ruta absoluta ROOT_PATH . '/logo.png':\n";
$logoPath = ROOT_PATH . '/logo.png';
echo "   Ruta: $logoPath\n";
if (file_exists($logoPath)) {
    echo "   ✓ Encontrado (CORRECTO)\n";
} else {
    echo "   ✗ NO encontrado (ERROR)\n";
    exit(1);
}

echo "\n3. Probando carga con ruta absoluta:\n";
try {
    $logo = EscposImage::load($logoPath, false);
    echo "   ✓ Logo cargado correctamente\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n4. Test de impresión real:\n";
try {
    $printerName = env('PRINTER_NAME', '');
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);
    $printer->initialize();
    
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    
    // Intentar con ruta absoluta (CORRECTO)
    $logo = EscposImage::load($logoPath, false);
    $printer->bitImage($logo);
    $printer->feed(1);
    
    $printer->text("TEST DESDE SERVIDOR WEB\n");
    $printer->text("Ruta absoluta funcionando\n");
    $printer->text(date('Y-m-d H:i:s') . "\n");
    $printer->feed(3);
    $printer->cut();
    $printer->close();
    
    echo "   ✓ Impresión exitosa\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n╔════════════════════════════════════════╗\n";
echo "║     ✓ PROBLEMA RESUELTO ✓              ║\n";
echo "╚════════════════════════════════════════╝\n\n";

echo "La solución fue usar ROOT_PATH . '/logo.png'\n";
echo "en lugar de solo 'logo.png'\n";

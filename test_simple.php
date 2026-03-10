<?php
/**
 * Test con código simplificado
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

echo "=== TEST CÓDIGO SIMPLIFICADO ===\n\n";

$printerName = env('PRINTER_NAME', '');

try {
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);
    $printer->initialize();
    
    echo "✓ Conectado a $printerName\n\n";
    
    // Código simplificado
    $logo = EscposImage::load("logo.png", false);
    $printer->bitImage($logo);
    $printer->feed(1);
    
    echo "✓ Logo cargado e impreso\n\n";
    
    // Texto de prueba
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("CODIGO SIMPLIFICADO\n");
    $printer->text(date('Y-m-d H:i:s') . "\n");
    $printer->feed(3);
    $printer->cut();
    $printer->close();
    
    echo "✓✓✓ IMPRESIÓN COMPLETADA\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

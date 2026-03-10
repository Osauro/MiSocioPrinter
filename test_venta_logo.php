<?php
/**
 * Test de impresión de ticket de venta con logo
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Tickets/VentaTicket.php';

use MiSocio\Database;
use MiSocio\Tickets\VentaTicket;

echo "=== TEST: BUSCAR VENTA REAL E IMPRIMIR ===\n\n";

try {
    $db = Database::connect();
    
    // Buscar la primera venta disponible
    $query = "SELECT id_venta, fecha, total FROM venta ORDER BY id_venta DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        echo "ℹ No hay ventas en la base de datos.\n";
        echo "Puedes probar accediendo a:\n";
        echo "http://localhost:5421/venta/1\n";
        exit(0);
    }
    
    echo "Venta encontrada:\n";
    echo "  - ID: {$venta['id_venta']}\n";
    echo "  - Fecha: {$venta['fecha']}\n";
    echo "  - Total: \${$venta['total']}\n\n";
    
    // Imprimir usando el endpoint real
    $url = "http://localhost:5421/venta/{$venta['id_venta']}";
    echo "Imprimiendo desde: $url\n";
    echo "(Ejecutando lógica de VentaTicket)...\n\n";
    
    $ticket = new VentaTicket();
    $ticket->print($venta['id_venta']);
    
    echo "✓✓✓ TICKET DE VENTA IMPRESO CORRECTAMENTE\n\n";
    echo "Verifica:\n";
    echo "1. Logo al inicio del ticket ✓\n";
    echo "2. Datos de la venta\n";
    echo "3. Lista de productos\n";
    echo "4. Total correcto\n";
    echo "5. Mensaje de pie de página\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

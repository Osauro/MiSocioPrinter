<?php
/**
 * Test de impresión completa con logo
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Tickets/TestTicket.php';

use MiSocio\Tickets\TestTicket;

echo "=== TEST DE TICKET COMPLETO CON LOGO ===\n\n";

// Verificar que existe el logo
$logoPath = ROOT_PATH . '/logo.png';
if (!file_exists($logoPath)) {
    echo "❌ ERROR: No existe logo.png en la raíz\n";
    exit(1);
}

$logoInfo = getimagesize($logoPath);
echo "✓ Logo encontrado:\n";
echo "  - Ruta: $logoPath\n";
echo "  - Dimensiones: {$logoInfo[0]}x{$logoInfo[1]}\n";
echo "  - Tamaño: " . round(filesize($logoPath)/1024, 2) . " KB\n\n";

// Verificar configuración
echo "Configuración:\n";
echo "  - SHOW_LOGO: " . (envBool('SHOW_LOGO', true) ? 'true' : 'false') . "\n";
echo "  - LOGO_IMAGE: " . env('LOGO_IMAGE', '') . "\n";
echo "  - PRINTER_NAME: " . env('PRINTER_NAME', '') . "\n\n";

try {
    echo "Imprimiendo ticket de prueba...\n";
    $ticket = new TestTicket();
    $ticket->print(0);
    
    echo "\n✓✓✓ TICKET IMPRESO EXITOSAMENTE\n\n";
    echo "Verifica el ticket físico:\n";
    echo "1. ¿Aparece el logo al inicio?\n";
    echo "2. ¿El logo se ve claro y centrado?\n";
    echo "3. ¿El texto del ticket es legible?\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

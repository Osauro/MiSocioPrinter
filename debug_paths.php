<?php
require_once __DIR__ . '/config/config.php';

// Base URL dinámica (igual que en index.php)
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$appBase   = $scriptDir === '/' ? '' : $scriptDir;

$logoImage = env('LOGO_IMAGE', '');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug - Rutas</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .info { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .path { background: #e9ecef; padding: 5px; margin: 5px 0; }
        .test { margin: 20px 0; padding: 15px; background: white; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>🔍 Debug - Información de Rutas</h1>
    
    <div class="info">
        <h3>Variables del Servidor:</h3>
        <div class="path"><strong>SCRIPT_NAME:</strong> <?= $_SERVER['SCRIPT_NAME'] ?? 'N/A' ?></div>
        <div class="path"><strong>DOCUMENT_ROOT:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></div>
        <div class="path"><strong>PHP_SELF:</strong> <?= $_SERVER['PHP_SELF'] ?? 'N/A' ?></div>
        <div class="path"><strong>REQUEST_URI:</strong> <?= $_SERVER['REQUEST_URI'] ?? 'N/A' ?></div>
    </div>
    
    <div class="info">
        <h3>Variables Calculadas:</h3>
        <div class="path"><strong>$scriptDir:</strong> "<?= $scriptDir ?>"</div>
        <div class="path"><strong>$appBase:</strong> "<?= $appBase ?>"</div>
        <div class="path"><strong>ROOT_PATH:</strong> <?= ROOT_PATH ?></div>
        <div class="path"><strong>UPLOADS_PATH:</strong> <?= UPLOADS_PATH ?></div>
    </div>
    
    <div class="info">
        <h3>Logo:</h3>
        <div class="path"><strong>LOGO_IMAGE (.env):</strong> "<?= $logoImage ?>"</div>
        <div class="path"><strong>Ruta construida:</strong> "<?= $appBase ?>/uploads/<?= $logoImage ?>"</div>
        <div class="path"><strong>Ruta absoluta:</strong> "/uploads/<?= $logoImage ?>"</div>
    </div>
    
    <div class="test">
        <h3>Test de Carga de Imagen:</h3>
        
        <h4>1. Con $appBase (actual):</h4>
        <img src="<?= $appBase ?>/uploads/<?= $logoImage ?>" 
             alt="Test con appBase" 
             style="max-height: 100px; border: 1px solid #ddd; background: #f9f9f9;"
             onerror="this.style.border='2px solid red'; this.alt='❌ Error cargando'">
        <div class="path">Ruta: <code><?= $appBase ?>/uploads/<?= $logoImage ?></code></div>
        
        <h4>2. Con ruta absoluta desde raíz:</h4>
        <img src="/uploads/<?= $logoImage ?>" 
             alt="Test absoluto" 
             style="max-height: 100px; border: 1px solid #ddd; background: #f9f9f9;"
             onerror="this.style.border='2px solid red'; this.alt='❌ Error cargando'">
        <div class="path">Ruta: <code>/uploads/<?= $logoImage ?></code></div>
        
        <h4>3. Con ruta relativa simple:</h4>
        <img src="uploads/<?= $logoImage ?>" 
             alt="Test relativo" 
             style="max-height: 100px; border: 1px solid #ddd; background: #f9f9f9;"
             onerror="this.style.border='2px solid red'; this.alt='❌ Error cargando'">
        <div class="path">Ruta: <code>uploads/<?= $logoImage ?></code></div>
    </div>
    
    <div class="info">
        <h3>Archivo físico existe:</h3>
        <?php 
        $filePath = UPLOADS_PATH . '/' . $logoImage;
        $exists = file_exists($filePath);
        ?>
        <div class="path">
            <strong><?= $filePath ?>:</strong> 
            <?= $exists ? '✅ SÍ EXISTE' : '❌ NO EXISTE' ?>
            <?php if ($exists): ?>
                <br>Tamaño: <?= number_format(filesize($filePath)) ?> bytes
            <?php endif; ?>
        </div>
    </div>
    
    <p style="margin-top: 30px;">
        <a href="index.php" style="color: #007bff;">← Volver al inicio</a>
    </p>
</body>
</html>

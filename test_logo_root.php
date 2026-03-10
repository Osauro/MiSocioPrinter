<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Logo en Raíz</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px; 
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        img { 
            max-width: 400px; 
            border: 2px solid #ddd; 
            padding: 10px; 
            margin: 20px 0;
            display: block;
        }
        .info { 
            background: #f0f0f0; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>✅ Test de Logo en Raíz</h1>
    
    <?php
    $logoFile = __DIR__ . '/logo.png';
    
    echo "<div class='info'>";
    echo "<h2>Información del archivo:</h2>";
    echo "<ul>";
    
    if (file_exists($logoFile)) {
        echo "<li class='success'>✓ El archivo logo.png existe en la raíz</li>";
        echo "<li>Ruta: " . $logoFile . "</li>";
        echo "<li>Tamaño: " . number_format(filesize($logoFile)) . " bytes</li>";
        
        $imageInfo = getimagesize($logoFile);
        echo "<li>Dimensiones: {$imageInfo[0]}x{$imageInfo[1]}</li>";
        echo "<li>Tipo: {$imageInfo['mime']}</li>";
    } else {
        echo "<li class='error'>✗ El archivo logo.png NO existe en la raíz</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>Vista previa:</h2>";
    echo "<img src='logo.png' alt='Logo' onerror=\"this.style.border='2px solid red'; this.alt='❌ Error al cargar la imagen'\">";
    
    echo "<div class='info'>";
    echo "<h3>URLs de acceso:</h3>";
    echo "<ul>";
    echo "<li>Relativa: <a href='logo.png' target='_blank'>logo.png</a></li>";
    echo "<li>Absoluta: <a href='/logo.png' target='_blank'>/logo.png</a></li>";
    echo "<li>Con puerto: <a href='http://localhost:5421/logo.png' target='_blank'>http://localhost:5421/logo.png</a></li>";
    echo "</ul>";
    echo "</div>";
    ?>
    
    <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
        ← Volver al Panel de Configuración
    </a>
</body>
</html>

<?php
echo "<h2>🔍 Buscador de Logs - Dónde están mis archivos</h2>\n";

// Buscar todos los archivos de log posibles
$buscar_en = [
    '/app/teveo/',
    '/tmp/',
    '/var/www/html/teveo/',
    '/var/log/',
    getcwd() . '/teveo/',
    getcwd() . '/'
];

echo "<strong>📂 Buscando archivos de log en todas las ubicaciones:</strong><br><br>\n";

$logs_encontrados = [];

foreach ($buscar_en as $directorio) {
    echo "<strong>Buscando en: $directorio</strong><br>\n";
    
    if (is_dir($directorio)) {
        echo "  ✅ Directorio existe<br>\n";
        echo "  📝 Permisos: " . substr(sprintf('%o', fileperms($directorio)), -4) . "<br>\n";
        echo "  ✍️ Escribible: " . (is_writable($directorio) ? "SÍ" : "NO") . "<br>\n";
        
        // Buscar archivos de log
        $archivos = glob($directorio . "*log*.txt");
        $archivos = array_merge($archivos, glob($directorio . "*accesos*.txt"));
        $archivos = array_merge($archivos, glob($directorio . "debug_*.txt"));
        $archivos = array_merge($archivos, glob($directorio . "api_log_*.txt"));
        
        if (!empty($archivos)) {
            foreach ($archivos as $archivo) {
                $size = filesize($archivo);
                $modified = date('Y-m-d H:i:s', filemtime($archivo));
                echo "  🎯 <strong style='color:green;'>ENCONTRADO: " . basename($archivo) . "</strong><br>\n";
                echo "     📏 Tamaño: $size bytes<br>\n";
                echo "     📅 Modificado: $modified<br>\n";
                
                $logs_encontrados[$archivo] = $size;
                
                // Mostrar contenido si es pequeño
                if ($size > 0 && $size < 2000) {
                    $contenido = file_get_contents($archivo);
                    echo "     📄 <strong>Contenido:</strong><br>\n";
                    echo "     <pre style='background:#f0f0f0; padding:5px; font-size:11px; max-height:200px; overflow:auto;'>" . htmlspecialchars($contenido) . "</pre><br>\n";
                } elseif ($size > 0) {
                    // Mostrar solo las últimas líneas
                    $contenido = file_get_contents($archivo);
                    $lineas = explode("\n", trim($contenido));
                    $ultimas_lineas = array_slice($lineas, -3);
                    echo "     📄 <strong>Últimas 3 líneas:</strong><br>\n";
                    echo "     <pre style='background:#f0f0f0; padding:5px; font-size:11px;'>" . htmlspecialchars(implode("\n", $ultimas_lineas)) . "</pre><br>\n";
                } else {
                    echo "     ⚠️ Archivo vacío<br>\n";
                }
            }
        } else {
            echo "  ❌ No se encontraron archivos de log<br>\n";
        }
    } else {
        echo "  ❌ Directorio no existe<br>\n";
    }
    echo "<br>\n";
}

echo "<hr><strong>📊 RESUMEN:</strong><br>\n";
if (empty($logs_encontrados)) {
    echo "❌ <strong style='color:red;'>No se encontraron archivos de log en ninguna ubicación</strong><br>\n";
    echo "🔧 Esto significa que los logs no se están guardando correctamente.<br>\n";
} else {
    echo "✅ <strong style='color:green;'>Se encontraron " . count($logs_encontrados) . " archivo(s) de log:</strong><br>\n";
    foreach ($logs_encontrados as $archivo => $size) {
        $estado = $size > 0 ? "CON DATOS ($size bytes)" : "VACÍO";
        echo "  📁 $archivo - $estado<br>\n";
    }
}

echo "<br><strong>🔧 PRUEBA EN VIVO:</strong><br>\n";
echo "Vamos a intentar escribir un log de prueba ahora mismo...<br><br>\n";

// Función de prueba de escritura
function probar_escritura($ruta) {
    $test_line = "[" . date('Y-m-d H:i:s') . "] TEST DE ESCRITURA MANUAL | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . " | Cuenta: TEST | Estado: PRUEBA MANUAL\n";
    
    echo "<strong>Probando escritura en: $ruta</strong><br>\n";
    
    // Crear directorio si no existe
    $dir = dirname($ruta);
    if (!is_dir($dir)) {
        $created = mkdir($dir, 0777, true);
        echo "  📁 Crear directorio: " . ($created ? "✅ ÉXITO" : "❌ FALLO") . "<br>\n";
        if ($created) {
            chmod($dir, 0777);
        }
    }
    
    // Intentar escribir
    $result = @file_put_contents($ruta, $test_line, FILE_APPEND);
    
    if ($result !== false) {
        echo "  ✅ <strong style='color:green;'>ESCRITURA EXITOSA</strong> ($result bytes)<br>\n";
        chmod($ruta, 0666);
        return true;
    } else {
        echo "  ❌ <strong style='color:red;'>ESCRITURA FALLÓ</strong><br>\n";
        return false;
    }
}

// Probar escritura en diferentes ubicaciones
$rutas_prueba = [
    '/app/teveo/log_accesos.txt',
    '/tmp/api_log_' . date('Y-m-d') . '.txt',
    getcwd() . '/teveo/log_accesos.txt'
];

$escritura_exitosa = false;
foreach ($rutas_prueba as $ruta) {
    if (probar_escritura($ruta)) {
        $escritura_exitosa = true;
        echo "  🎯 <strong>ESTA UBICACIÓN FUNCIONA PARA LOS LOGS</strong><br>\n";
    }
    echo "<br>\n";
}

if (!$escritura_exitosa) {
    echo "<strong style='color:red;'>⚠️ PROBLEMA: No se puede escribir en ninguna ubicación</strong><br>\n";
    echo "Esto explica por qué tus logs están vacíos.<br>\n";
}

echo "<br><strong>💡 RECOMENDACIÓN:</strong><br>\n";
if ($escritura_exitosa) {
    echo "✅ Al menos una ubicación funciona. Actualiza tu index.php para usar la ruta que funciona.<br>\n";
} else {
    echo "❌ Hay un problema de permisos. Necesitas revisar la configuración del servidor.<br>\n";
}
?>
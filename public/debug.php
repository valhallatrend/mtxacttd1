<?php
// Archivo de debugging para diagnosticar el problema de logs
$log_file = "/app/teveo/log_accesos.txt";

echo "<h2>Diagnóstico del sistema de logs</h2>\n";

// 1. Verificar la ruta actual
echo "<strong>1. Información del sistema:</strong><br>\n";
echo "Directorio actual: " . getcwd() . "<br>\n";
echo "Ruta del log: $log_file<br>\n";
echo "Usuario del servidor web: " . get_current_user() . "<br>\n";
echo "UID/GID: " . getmyuid() . "/" . getmygid() . "<br>\n";

// Función para obtener IP real (copia de la función del index.php)
function obtener_ip_real_test() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',  
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

// 2. Verificar si el directorio existe
$log_dir = dirname($log_file);
echo "<br><strong>2. Estado del directorio:</strong><br>\n";
echo "Directorio del log: $log_dir<br>\n";
echo "¿Existe el directorio? " . (is_dir($log_dir) ? "SÍ" : "NO") . "<br>\n";

if (!is_dir($log_dir)) {
    echo "Intentando crear el directorio...<br>\n";
    $created = mkdir($log_dir, 0777, true);
    echo "¿Se creó? " . ($created ? "SÍ" : "NO") . "<br>\n";
    if ($created) {
        chmod($log_dir, 0777);
        echo "Permisos establecidos a 0777<br>\n";
    }
}

if (is_dir($log_dir)) {
    echo "Permisos del directorio: " . substr(sprintf('%o', fileperms($log_dir)), -4) . "<br>\n";
    echo "¿Es escribible? " . (is_writable($log_dir) ? "SÍ" : "NO") . "<br>\n";
}

// 3. Verificar el archivo de log
echo "<br><strong>3. Estado del archivo:</strong><br>\n";
echo "¿Existe el archivo? " . (file_exists($log_file) ? "SÍ" : "NO") . "<br>\n";

if (file_exists($log_file)) {
    echo "Permisos del archivo: " . substr(sprintf('%o', fileperms($log_file)), -4) . "<br>\n";
    echo "¿Es escribible? " . (is_writable($log_file) ? "SÍ" : "NO") . "<br>\n";
    echo "Tamaño: " . filesize($log_file) . " bytes<br>\n";
    echo "Propietario: " . fileowner($log_file) . "<br>\n";
}

// 4. Intentar escribir con los nuevos campos
echo "<br><strong>4. Test de escritura con nuevos campos:</strong><br>\n";
$test_line = "[" . date('Y-m-d H:i:s') . "] TEST DE ESCRITURA | IP: " . obtener_ip_real_test() . " | Cuenta: 12345 | Broker: Test | Versión: 2.7-1 | Estado: TEST | Nombre: Usuario Test | Balance: 1000.00 | Agent: Debug Script\n";

try {
    $result = file_put_contents($log_file, $test_line, FILE_APPEND);
    if ($result !== false) {
        echo "✅ Escritura exitosa! Bytes escritos: $result<br>\n";
        chmod($log_file, 0666);
        echo "✅ Permisos del archivo actualizados<br>\n";
    } else {
        echo "❌ Error: file_put_contents retornó false<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Excepción capturada: " . $e->getMessage() . "<br>\n";
}

// 5. Verificar contenido
if (file_exists($log_file)) {
    echo "<br><strong>5. Contenido actual del archivo:</strong><br>\n";
    $content = file_get_contents($log_file);
    if ($content === false) {
        echo "❌ No se pudo leer el archivo<br>\n";
    } else {
        echo "Contenido (" . strlen($content) . " caracteres):<br>\n";
        echo "<pre>" . htmlspecialchars($content) . "</pre>\n";
    }
}

// 6. Test de rutas alternativas
echo "<br><strong>6. Rutas alternativas:</strong><br>\n";
$alt_paths = [
    "/tmp/test_log.txt",
    getcwd() . "/test_log.txt",
    "/app/test_log.txt"
];

foreach ($alt_paths as $path) {
    $dir = dirname($path);
    echo "Probando: $path<br>\n";
    echo "- Directorio existe: " . (is_dir($dir) ? "SÍ" : "NO") . "<br>\n";
    echo "- Directorio escribible: " . (is_writable($dir) ? "SÍ" : "NO") . "<br>\n";
    
    $test_result = @file_put_contents($path, "test\n");
    echo "- Test escritura: " . ($test_result !== false ? "✅ OK ($test_result bytes)" : "❌ FALLO") . "<br>\n";
    
    if ($test_result !== false) {
        @unlink($path); // Limpiar
    }
    echo "<br>\n";
}

echo "<br><strong>7. Headers relacionados con IP:</strong><br>\n";
$ip_headers = [
    'REMOTE_ADDR',
    'HTTP_CF_CONNECTING_IP',
    'HTTP_X_REAL_IP', 
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_FORWARDED',
    'HTTP_X_CLUSTER_CLIENT_IP',
    'HTTP_FORWARDED_FOR',
    'HTTP_FORWARDED'
];

foreach ($ip_headers as $header) {
    $value = $_SERVER[$header] ?? null;
    echo "$header: " . ($value ? $value : "No definido") . "<br>\n";
}

echo "<br><strong>9. Ubicación actual de los logs:</strong><br>\n";
echo "Ruta configurada: <strong>$log_file</strong><br>\n";

// Verificar si existen logs en diferentes ubicaciones posibles
$posibles_logs = [
    "/app/teveo/log_accesos.txt",
    "/tmp/api_log_" . date('Y-m-d') . ".txt",
    "/tmp/debug_log_" . date('Y-m-d') . ".txt",
    getcwd() . "/teveo/log_accesos.txt",
    "/var/www/html/teveo/log_accesos.txt"
];

foreach ($posibles_logs as $log_path) {
    if (file_exists($log_path)) {
        $size = filesize($log_path);
        $modified = date('Y-m-d H:i:s', filemtime($log_path));
        echo "✅ <strong>ENCONTRADO:</strong> $log_path (${size} bytes, modificado: $modified)<br>\n";
        
        // Mostrar las últimas 3 líneas
        $content = file_get_contents($log_path);
        $lines = explode("\n", trim($content));
        $last_lines = array_slice($lines, -3);
        
        echo "<strong>Últimas entradas:</strong><br>\n";
        echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px;'>" . htmlspecialchars(implode("\n", $last_lines)) . "</pre><br>\n";
    } else {
        echo "❌ No existe: $log_path<br>\n";
    }
}

echo "<br><strong>10. Variables de entorno relevantes:</strong><br>\n";
$env_vars = ['HOME', 'USER', 'TMPDIR', 'DOCUMENT_ROOT'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo "$var: " . ($value ? $value : "No definida") . "<br>\n";
}
?>
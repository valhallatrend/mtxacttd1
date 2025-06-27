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

// 4. Intentar escribir
echo "<br><strong>4. Test de escritura:</strong><br>\n";
$test_line = "[" . date('Y-m-d H:i:s') . "] TEST DE ESCRITURA DESDE DEBUG\n";

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

echo "<br><strong>8. Variables de entorno relevantes:</strong><br>\n";
$env_vars = ['HOME', 'USER', 'TMPDIR', 'DOCUMENT_ROOT'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo "$var: " . ($value ? $value : "No definida") . "<br>\n";
}
?>
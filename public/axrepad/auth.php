<?php
function auth() {
    $url = 'https://axslsp.onrender.com/asxp.php';
    
    // Log de debug (opcional - comentar en producción)
    $debug_mode = true; // Cambiar a false en producción
    
    if ($debug_mode) {
        error_log("Auth: Iniciando autenticación con $url");
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,           // Aumentado de 5 a 15 segundos
        CURLOPT_CONNECTTIMEOUT => 10,    // Timeout de conexión
        CURLOPT_SSL_VERIFYPEER => false, // Para evitar problemas SSL
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,  // Seguir redirects
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AuthSystem/1.0)', // User agent más estándar
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Cache-Control: no-cache',
            'Connection: close'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    curl_close($ch);
    
    // Verificar errores de cURL
    if ($curl_errno !== 0) {
        if ($debug_mode) {
            error_log("Auth: Error cURL [$curl_errno]: $curl_error");
        }
        echo "Error de conexión con el servidor de autenticación. Código: $curl_errno - $curl_error";
        exit;
    }
    
    // Verificar código HTTP
    if ($http_code !== 200) {
        if ($debug_mode) {
            error_log("Auth: HTTP error code: $http_code");
        }
        echo "El servidor de autenticación respondió con código HTTP: $http_code";
        exit;
    }
    
    // Verificar que hay respuesta
    if ($response === false || empty($response)) {
        if ($debug_mode) {
            error_log("Auth: Respuesta vacía del servidor");
        }
        echo "Respuesta vacía del servidor de autenticación.";
        exit;
    }
    
    if ($debug_mode) {
        error_log("Auth: Respuesta recibida (primeros 200 chars): " . substr($response, 0, 200));
    }
    
    // Limpiar la respuesta de posibles caracteres problemáticos
    $response = trim($response);
    
    // Remover BOM UTF-8 si existe
    if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
        $response = substr($response, 3);
    }
    
    // Remover caracteres de control que pueden interferir con JSON
    $response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $response);
    
    // Intentar decodificar JSON
    $users = json_decode($response, true);
    $json_error = json_last_error();
    
    if ($json_error !== JSON_ERROR_NONE) {
        if ($debug_mode) {
            error_log("Auth: Error JSON: " . json_last_error_msg());
            error_log("Auth: Contenido problemático: " . $response);
        }
        
        // Mensaje más descriptivo del error
        $error_msg = "Respuesta inválida del servidor de autenticación. ";
        $error_msg .= "Error JSON: " . json_last_error_msg();
        
        if ($debug_mode) {
            $error_msg .= " (Contenido: " . substr($response, 0, 100) . "...)";
        }
        
        echo $error_msg;
        exit;
    }
    
    // Verificar que la respuesta es un array
    if (!is_array($users)) {
        if ($debug_mode) {
            error_log("Auth: La respuesta no es un array. Tipo: " . gettype($users));
        }
        echo "Formato de respuesta inválido del servidor de autenticación (no es array).";
        exit;
    }
    
    // Verificar que hay usuarios
    if (empty($users)) {
        if ($debug_mode) {
            error_log("Auth: Array de usuarios vacío");
        }
        echo "No hay usuarios configurados en el servidor de autenticación.";
        exit;
    }
    
    if ($debug_mode) {
        error_log("Auth: " . count($users) . " usuario(s) cargado(s) correctamente");
    }
    
    // Obtener credenciales del usuario
    $u = $_SERVER['PHP_AUTH_USER'] ?? '';
    $p = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    if ($debug_mode) {
        error_log("Auth: Verificando usuario: " . $u);
    }
    
    // Verificar credenciales
    foreach ($users as $index => $user) {
        // Verificar que cada usuario tiene la estructura correcta
        if (!is_array($user) || !isset($user['u']) || !isset($user['p'])) {
            if ($debug_mode) {
                error_log("Auth: Usuario en índice $index tiene estructura inválida");
            }
            continue;
        }
        
        if ($user['u'] === $u && $user['p'] === $p) {
            if ($debug_mode) {
                error_log("Auth: Autenticación exitosa para usuario: $u");
            }
            return true;
        }
    }
    
    if ($debug_mode) {
        error_log("Auth: Credenciales inválidas para usuario: $u");
    }
    
    // Credenciales inválidas
    header('WWW-Authenticate: Basic realm="Acceso restringido"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Acceso denegado');
}

// Función auxiliar para debug (usar solo en desarrollo)
function auth_test() {
    echo "<h2>Test de Autenticación</h2>";
    
    try {
        auth();
        echo "✅ Autenticación exitosa";
    } catch (Exception $e) {
        echo "❌ Error en autenticación: " . $e->getMessage();
    }
}
?>

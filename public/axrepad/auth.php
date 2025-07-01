<?php
function auth() {
    $url = 'https://axslsp.onrender.com/asxp.php';
    
    // Usuarios de respaldo local (por si el servidor falla)
    $backup_users = [
        ['u' => 'admin', 'p' => 'pass1234'],    // Mismo que el servidor remoto
        ['u' => 'gestor', 'p' => 'clave5678']   // Mismo que el servidor remoto
    ];
    
    $users = null;
    $auth_source = 'backup';
    
    // Intentar servidor remoto con configuración optimizada
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,              // Aumentado para servicios de Render
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'AuthSystem/Final',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Cache-Control: no-cache'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Procesar respuesta del servidor remoto
    if (!$curl_error && $http_code == 200 && $response) {
        $response = trim($response);
        
        // Limpiar BOM UTF-8 si existe
        if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
            $response = substr($response, 3);
        }
        
        $remote_users = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($remote_users) && !empty($remote_users)) {
            $users = $remote_users;
            $auth_source = 'remote';
            error_log("Auth: Usando servidor remoto - " . count($remote_users) . " usuarios cargados");
        } else {
            error_log("Auth: Servidor remoto devolvió JSON inválido: " . json_last_error_msg());
        }
    } else {
        error_log("Auth: Servidor remoto falló - HTTP: $http_code, Error: " . ($curl_error ?: 'ninguno'));
    }
    
    // Usar respaldo local si remoto falla
    if (!$users) {
        $users = $backup_users;
        $auth_source = 'backup';
        error_log("Auth: Usando usuarios de respaldo local");
    }
    
    // Verificar credenciales
    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    foreach ($users as $user) {
        if (is_array($user) && isset($user['u']) && isset($user['p'])) {
            if ($user['u'] === $username && $user['p'] === $password) {
                error_log("Auth: Login exitoso para '$username' usando $auth_source");
                return true;
            }
        }
    }
    
    // Credenciales inválidas
    error_log("Auth: Credenciales inválidas para '$username'");
    
    header('WWW-Authenticate: Basic realm="Panel de Licencias"');
    header('HTTP/1.0 401 Unauthorized');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Acceso Requerido</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 50px; }
        .auth-box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); text-align: center; }
        .title { color: #495057; font-size: 24px; margin-bottom: 20px; }
        .message { color: #6c757d; margin-bottom: 20px; line-height: 1.5; }
        .credentials { background: #e9ecef; padding: 15px; border-radius: 5px; font-size: 14px; color: #495057; }
        .status { margin-top: 15px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="title">🔐 Acceso Requerido</div>
        <div class="message">
            Para acceder al panel de licencias, debe proporcionar credenciales válidas.
        </div>
        <div class="credentials">
            <strong>Usuarios disponibles:</strong><br>
            • admin / pass1234<br>
            • gestor / clave5678
        </div>
        <div class="status">
            Sistema de autenticación: ' . ucfirst($auth_source) . '<br>
            ' . ($auth_source === 'remote' ? '✅ Servidor remoto activo' : '🔄 Usando respaldo local') . '
        </div>
    </div>
</body>
</html>';
    
    exit;
}
?>

<?php
// auth.php
function auth() {
    $url = 'https://axslsp.onrender.com/asxp.php';

    // Usuarios de respaldo local
    $backup_users = [
        ['u' => 'admin', 'p' => 'pass1234'],
        ['u' => 'gestor', 'p' => 'clave5678']
    ];

    $users = null;
    $auth_source = 'backup';

    // Intentar obtener usuarios del servidor remoto
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
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

    if (!$curl_error && $http_code == 200 && $response) {
        $response = trim($response);
        if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
            $response = substr($response, 3);
        }
        $remote_users = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($remote_users)) {
            $users = $remote_users;
            $auth_source = 'remote';
        }
    }

    // Usar respaldo si remoto falla
    if (!$users) {
        sleep(15); // espera por si render sigue despertando
        $users = $backup_users;
        $auth_source = 'backup';
    }

    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';

    foreach ($users as $user) {
        if ($user['u'] === $username && $user['p'] === $password) {
            return true;
        }
    }

    // Fallo en autenticación
    header('WWW-Authenticate: Basic realm="Panel de Licencias"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<!DOCTYPE html><html><head><title>Acceso Requerido</title><style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 50px; }
    .auth-box { max-width: 400px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); text-align: center; }
    .title { color: #495057; font-size: 24px; margin-bottom: 20px; }
    .message { color: #6c757d; margin-bottom: 20px; line-height: 1.5; }
    .credentials { background: #e9ecef; padding: 15px; border-radius: 5px; font-size: 14px; color: #495057; }
    .status { margin-top: 15px; font-size: 12px; color: #6c757d; }
    </style></head><body><div class="auth-box">
    <div class="title">🔐 Acceso Requerido</div>
    <div class="message">Debe ingresar credenciales válidas para acceder al panel.</div>
    <div class="credentials">Usuarios:<br>• admin / pass1234<br>• gestor / clave5678</div>
    <div class="status">Autenticación por: ' . ucfirst($auth_source) . '</div>
    </div></body></html>';
    exit;
}
?>

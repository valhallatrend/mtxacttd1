<?php
function auth() {
    $url = 'https://axslsp.onrender.com/index.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // tiempo de espera
    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error al conectar con el servidor de autenticación.";
        exit;
    }

    curl_close($ch);

    $users = json_decode($res, true);
    if (!is_array($users)) {
        echo "Respuesta inválida del servidor de autenticación.";
        exit;
    }

    $u = $_SERVER['PHP_AUTH_USER'] ?? '';
    $p = $_SERVER['PHP_AUTH_PW'] ?? '';

    foreach ($users as $c) {
        if ($c['u'] === $u && $c['p'] === $p) return true;
    }

    header('WWW-Authenticate: Basic realm="Acceso restringido"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Acceso denegado');
}

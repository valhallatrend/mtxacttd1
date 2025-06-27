<?php
header('Content-Type: application/json');

// Soporte para POST y GET
$cuenta  = $_POST['account'] ?? $_GET['account'] ?? '';
$broker  = $_POST['broker'] ?? $_GET['broker'] ?? '';
$version = $_POST['ea_version'] ?? $_GET['ea_version'] ?? '';

// Ruta del log
$log_file = __DIR__ . "/log_accesos.txt";

// Función para guardar logs
function registrar_log($cuenta, $broker, $version, $estado) {
    global $log_file;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $fecha = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
    $linea = "[$fecha] IP: $ip | Cuenta: $cuenta | Broker: $broker | Versión: $version | Estado: $estado | Agent: $user_agent\n";
    file_put_contents($log_file, $linea, FILE_APPEND);
}

if (!$cuenta || !is_numeric($cuenta)) {
    registrar_log($cuenta, $broker, $version, "Cuenta no especificada");
    echo json_encode(["ok" => false, "error" => "Cuenta no especificada"]);
    exit;
}

try {
    $db = new PDO('sqlite:/app/db/licencias.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM licencias WHERE cuenta = :cuenta");
    $stmt->execute([':cuenta' => $cuenta]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        registrar_log($cuenta, $broker, $version, "Cuenta no registrada");
        echo json_encode(["ok" => false, "error" => "Cuenta no registrada"]);
        exit;
    }

    $hoy = date('Y-m-d');

    if (strtolower($row['estado']) !== 'activo') {
        registrar_log($cuenta, $broker, $version, "Licencia inactiva");
        echo json_encode(["ok" => false, "error" => "Licencia inactiva"]);
        exit;
    }

    if ($row['expira'] < $hoy) {
        registrar_log($cuenta, $broker, $version, "Licencia expirada");
        echo json_encode(["ok" => false, "error" => "Licencia expirada"]);
        exit;
    }

    if (!empty($row['version_permitida']) && $version !== $row['version_permitida']) {
        registrar_log($cuenta, $broker, $version, "Versión no autorizada");
        echo json_encode(["ok" => false, "error" => "Versión del EA no autorizada"]);
        exit;
    }

    registrar_log($cuenta, $broker, $version, "Licencia válida");

    echo json_encode([
        "ok" => true,
        "status" => "VALIDA",
        "cuenta" => $cuenta,
        "tipo" => strtoupper($row['tipo']),
        "expira" => $row['expira'],
        "max_posiciones" => (int)$row['max_posiciones'],
        "estado" => $row['estado'],
        "broker" => $broker,
        "ea_version" => $version
    ]);

} catch (Exception $e) {
    registrar_log($cuenta, $broker, $version, "Error interno");
    echo json_encode(["ok" => false, "error" => "Error interno"]);
}

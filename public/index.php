<?php
header('Content-Type: application/json');

// Soporte para POST y GET
$cuenta  = $_POST['account'] ?? $_GET['account'] ?? '';
$broker  = $_POST['broker'] ?? $_GET['broker'] ?? '';
$version = $_POST['ea_version'] ?? $_GET['ea_version'] ?? '';

if (!$cuenta || !is_numeric($cuenta)) {
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
        echo json_encode(["ok" => false, "error" => "Cuenta no registrada"]);
        exit;
    }

    $hoy = date('Y-m-d');

    if (strtolower($row['estado']) !== 'activo') {
        echo json_encode(["ok" => false, "error" => "Licencia inactiva"]);
        exit;
    }

    if ($row['expira'] < $hoy) {
        echo json_encode(["ok" => false, "error" => "Licencia expirada"]);
        exit;
    }

    if (!empty($row['version_permitida']) && $version !== $row['version_permitida']) {
        echo json_encode(["ok" => false, "error" => "Versión del EA no autorizada"]);
        exit;
    }

    echo json_encode([
        "ok" => true,
        "cuenta" => $cuenta,
        "tipo" => strtoupper($row['tipo']),
        "expira" => $row['expira'],
        "max_posiciones" => (int)$row['max_posiciones'],
        "estado" => $row['estado'],
        "broker" => $broker,
        "ea_version" => $version
    ]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => "Error interno"]);
}

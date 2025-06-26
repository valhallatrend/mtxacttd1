<?php
header('Content-Type: application/json');

$db = new PDO('sqlite:/app/db/licencias.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cuenta = $_GET['account'] ?? '';

if (!$cuenta) {
    echo json_encode(["ok" => false, "error" => "Cuenta no especificada"]);
    exit;
}

$stmt = $db->prepare("SELECT * FROM licencias WHERE cuenta = :cuenta");
$stmt->execute([':cuenta' => $cuenta]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["ok" => false, "error" => "Cuenta no encontrada"]);
    exit;
}

$hoy = date('Y-m-d');
if ($row['estado'] !== 'activo') {
    echo json_encode(["ok" => false, "error" => "Licencia inactiva"]);
    exit;
}
if ($row['expira'] < $hoy) {
    echo json_encode(["ok" => false, "error" => "Licencia expirada"]);
    exit;
}

echo json_encode([
    "ok" => true,
    "tipo" => $row['tipo'],
    "expira" => $row['expira'],
    "max_posiciones" => $row['max_posiciones'],
    "estado" => $row['estado']
]);

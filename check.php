<?php
header('Content-Type: application/json');

// Parámetros recibidos del EA
$cuenta = $_GET['account'] ?? '';
$broker = $_GET['broker'] ?? '';
$version = $_GET['ea_version'] ?? '';

// Validaciones mínimas
if (!$cuenta || !is_numeric($cuenta)) {
    echo json_encode(["ok" => false, "error" => "Cuenta inválida"]);
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

    if (strtoupper($row['estado']) !== 'ACTIVO') {
        echo json_encode(["ok" => false, "error" => "Licencia inactiva"]);
        exit;
    }

    if ($row['expira'] < $hoy) {
        echo json_encode(["ok" => false, "error" => "Licencia expirada"]);
        exit;
    }

    // Respuesta final
    echo json_encode([
        "ok" => true,
        "cuenta" => $cuenta,
        "tipo" => strtoupper($row['tipo']), // DEMO, BASICO, VIP
        "expira" => $row['expira'],
        "max_posiciones" => (int)$row['max_posiciones'],
        "broker" => $broker,
        "ea_version" => $version
    ]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => "Error interno"]);
}

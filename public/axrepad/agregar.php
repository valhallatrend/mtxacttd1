<?php
require_once('auth.php');
auth();

$db = new PDO('sqlite:/app/db/licencias.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO licencias (cuenta, tipo, expira, max_posiciones, email, comentario, estado, version_permitida)
                          VALUES (:cuenta, :tipo, :expira, :max_posiciones, :email, :comentario, :estado, :version_permitida)');
    $stmt->execute([
        ':cuenta' => $_POST['cuenta'],
        ':tipo' => $_POST['tipo'],
        ':expira' => $_POST['expira'],
        ':max_posiciones' => $_POST['max_posiciones'],
        ':email' => $_POST['email'],
        ':comentario' => $_POST['comentario'],
        ':estado' => $_POST['estado'],
        ':version_permitida' => $_POST['version_permitida']
    ]);
    echo "<p style='color: green;'>Licencia agregada exitosamente.</p>";
}
?>

<form method="POST">
    <label>Cuenta: <input name="cuenta" required></label><br>
    <label>Tipo: 
        <select name="tipo">
            <option value="DEMO">DEMO</option>
            <option value="BASICO">BASICO</option>
            <option value="VIP">VIP</option>
        </select>
    </label><br>
    <label>Expira: <input type="date" name="expira" required></label><br>
    <label>Máx Posiciones: <input type="number" name="max_posiciones" required></label><br>
    <label>Email: <input name="email"></label><br>
    <label>Comentario: <input name="comentario"></label><br>
    <label>Estado: 
        <select name="estado">
            <option value="activo">activo</option>
            <option value="inactivo">inactivo</option>
        </select>
    </label><br>
    <label>Versión permitida: <input name="version_permitida" placeholder="ej: 2.7-1"></label><br>
    <button type="submit">Agregar</button>
</form>

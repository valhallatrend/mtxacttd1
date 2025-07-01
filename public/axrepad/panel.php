<?php
require_once(__DIR__ . '/auth.php');
auth();

$db = new PDO('sqlite:/app/db/licencias.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_GET['eliminar'])) {
    $stmt = $db->prepare('DELETE FROM licencias WHERE cuenta = :cuenta');
    $stmt->execute([':cuenta' => $_GET['eliminar']]);
    echo "<p style='color:red;'>Licencia eliminada.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cuenta_original'])) {
    $stmt = $db->prepare('UPDATE licencias SET cuenta=:cuenta, tipo=:tipo, expira=:expira, max_posiciones=:max_posiciones,
                         email=:email, comentario=:comentario, estado=:estado, version_permitida=:version_permitida
                         WHERE cuenta=:cuenta_original');
    $stmt->execute([
        ':cuenta_original' => $_POST['cuenta_original'],
        ':cuenta' => $_POST['cuenta'],
        ':tipo' => $_POST['tipo'],
        ':expira' => $_POST['expira'],
        ':max_posiciones' => $_POST['max_posiciones'],
        ':email' => $_POST['email'],
        ':comentario' => $_POST['comentario'],
        ':estado' => $_POST['estado'],
        ':version_permitida' => $_POST['version_permitida']
    ]);
    echo "<p style='color:green;'>Licencia actualizada.</p>";
}

#revision de update
$db_file = 'licencias.sqlite';
$full_path = realpath($db_file);

// Modo debug
echo "<h3>🧪 DEBUG DE ACTUALIZACIÓN</h3>";
echo "<strong>📍 Ruta de la base de datos usada:</strong> $full_path<br>";

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cambia estos valores por los que estés usando en el UPDATE real
    $id = 1; // ID de prueba
    $nueva_fecha = '2025-07-30';

    $stmt = $db->prepare("UPDATE licencias SET vencimiento = :v WHERE id = :id");
    $success = $stmt->execute([':v' => $nueva_fecha, ':id' => $id]);

    if ($success) {
        echo "✅ UPDATE ejecutado con éxito.<br>";
    } else {
        echo "⚠️ UPDATE no aplicó.<br>";
    }

    // Confirmación de cambio
    $result = $db->query("SELECT id, vencimiento FROM licencias WHERE id = $id");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "🗓️ Fecha actual de ID $id: " . $row['vencimiento'] . "<br>";

} catch (PDOException $e) {
    echo "❌ Error en la base de datos: " . $e->getMessage();
}



$licencias = $db->query('SELECT * FROM licencias ORDER BY cuenta')->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Panel de Licencias</h2>
<table border="1" cellpadding="5">
<tr>
<th>Cuenta</th><th>Tipo</th><th>Expira</th><th>Máx Pos.</th><th>Email</th><th>Comentario</th><th>Estado</th><th>Versión</th><th>Acción</th>
</tr>
<?php foreach ($licencias as $lic) { ?>
<tr><form method="POST">
<td><input name="cuenta" value="<?=$lic['cuenta']?>"></td>
<td>
    <select name="tipo">
        <option value="DEMO" <?=$lic['tipo']=='DEMO'?'selected':''?>>DEMO</option>
        <option value="BASICO" <?=$lic['tipo']=='BASICO'?'selected':''?>>BASICO</option>
        <option value="VIP" <?=$lic['tipo']=='VIP'?'selected':''?>>VIP</option>
    </select>
</td>
<td><input type="date" name="expira" value="<?=$lic['expira']?>"></td>
<td><input name="max_posiciones" type="number" value="<?=$lic['max_posiciones']?>"></td>
<td><input name="email" value="<?=$lic['email']?>"></td>
<td><input name="comentario" value="<?=$lic['comentario']?>"></td>
<td>
    <select name="estado">
        <option value="activo" <?=$lic['estado']=='activo'?'selected':''?>>activo</option>
        <option value="inactivo" <?=$lic['estado']=='inactivo'?'selected':''?>>inactivo</option>
    </select>
</td>
<td><input name="version_permitida" value="<?=$lic['version_permitida'] ?? ''?>"></td>
<td>
    <input type="hidden" name="cuenta_original" value="<?=$lic['cuenta']?>">
    <button type="submit">Guardar</button>
    <a href="?eliminar=<?=$lic['cuenta']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a>
</td>
</form></tr>
<?php } ?>
</table>

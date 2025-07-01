<?php
// Panel temporal SIN autenticación para diagnosticar el problema
echo "<div style='background:#fff3cd; border:1px solid #ffeaa7; padding:10px; margin:10px 0; border-radius:5px;'>";
echo "⚠️ <strong>MODO DE EMERGENCIA:</strong> Panel funcionando sin autenticación para diagnóstico.<br>";
echo "🔒 Recuerda restaurar la autenticación una vez solucionado el problema.";
echo "</div>";

// Comentar estas líneas problemáticas temporalmente
// require_once('auth.php');
// auth();

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

$licencias = $db->query('SELECT * FROM licencias ORDER BY cuenta')->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel de Licencias - Modo Emergencia</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input, select { width: 100%; padding: 4px; }
        button { background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .alert { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>

<h2>🛠️ Panel de Licencias (Modo Emergencia)</h2>

<div class="alert">
    <strong>📋 Diagnóstico recomendado:</strong><br>
    1. <a href="auth_debug.php" target="_blank">🔍 Ejecutar diagnóstico de auth.php</a><br> 
    2. Verificar que el archivo auth.php existe y tiene la función auth()<br>
    3. Una vez corregido, restaurar la autenticación en panel.php
</div>

<table>
<tr>
<th>Cuenta</th><th>Tipo</th><th>Expira</th><th>Máx Pos.</th><th>Email</th><th>Comentario</th><th>Estado</th><th>Versión</th><th>Acción</th>
</tr>
<?php foreach ($licencias as $lic) { ?>
<tr><form method="POST">
<td><input name="cuenta" value="<?=htmlspecialchars($lic['cuenta'])?>"></td>
<td>
    <select name="tipo">
        <option value="DEMO" <?=$lic['tipo']=='DEMO'?'selected':''?>>DEMO</option>
        <option value="BASICO" <?=$lic['tipo']=='BASICO'?'selected':''?>>BASICO</option>
        <option value="VIP" <?=$lic['tipo']=='VIP'?'selected':''?>>VIP</option>
    </select>
</td>
<td><input type="date" name="expira" value="<?=htmlspecialchars($lic['expira'])?>"></td>
<td><input name="max_posiciones" type="number" value="<?=htmlspecialchars($lic['max_posiciones'])?>"></td>
<td><input name="email" value="<?=htmlspecialchars($lic['email'])?>"></td>
<td><input name="comentario" value="<?=htmlspecialchars($lic['comentario'])?>"></td>
<td>
    <select name="estado">
        <option value="activo" <?=$lic['estado']=='activo'?'selected':''?>>activo</option>
        <option value="inactivo" <?=$lic['estado']=='inactivo'?'selected':''?>>inactivo</option>
    </select>
</td>
<td><input name="version_permitida" value="<?=htmlspecialchars($lic['version_permitida'] ?? '')?>"></td>
<td>
    <input type="hidden" name="cuenta_original" value="<?=htmlspecialchars($lic['cuenta'])?>">
    <button type="submit">Guardar</button>
    <a href="?eliminar=<?=htmlspecialchars($lic['cuenta'])?>" onclick="return confirm('¿Eliminar?')" style="color:red; text-decoration:none; margin-left:10px;">❌</a>
</td>
</form></tr>
<?php } ?>
</table>

<div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
    <h3>🔧 Herramientas de diagnóstico:</h3>
    <a href="auth_debug.php" style="background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;">🔍 Diagnosticar auth.php</a>
    <a href="wake_service.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;">🔄 Verificar servicio externo</a>
</div>

</body>
</html>

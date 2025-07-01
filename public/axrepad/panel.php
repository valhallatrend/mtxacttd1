<?php
require_once(__DIR__ . '/auth.php');
auth();

$db = new PDO('sqlite:/app/db/licencias.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Función de debug mejorada
function debugLog($message) {
    echo "<div style='background:#f0f0f0; padding:5px; margin:5px 0; border-left:3px solid #007cba;'>";
    echo "<strong>DEBUG:</strong> $message";
    echo "</div>";
}

// ===========================================
// SECCIÓN DE DEBUG INICIAL
// ===========================================
echo "<h3>🧪 DEBUG DE CONEXIÓN Y ESTRUCTURA</h3>";

$db_file = '/app/db/licencias.sqlite';
$full_path = realpath($db_file);
echo "<strong>📍 Ruta de la base de datos:</strong> $full_path<br>";

try {
    // Verificar estructura de la tabla
    $columns = $db->query("PRAGMA table_info(licencias)")->fetchAll(PDO::FETCH_ASSOC);
    echo "<strong>📋 Estructura de la tabla 'licencias':</strong><br>";
    foreach ($columns as $col) {
        echo "- {$col['name']} ({$col['type']})<br>";
    }
    echo "<hr>";
} catch (PDOException $e) {
    echo "❌ Error verificando estructura: " . $e->getMessage() . "<br>";
}

// ===========================================
// PROCESAMIENTO DE ELIMINACIÓN
// ===========================================
if (isset($_GET['eliminar'])) {
    try {
        debugLog("Intentando eliminar cuenta: " . $_GET['eliminar']);
        
        $stmt = $db->prepare('DELETE FROM licencias WHERE cuenta = :cuenta');
        $result = $stmt->execute([':cuenta' => $_GET['eliminar']]);
        $rowsAffected = $stmt->rowCount();
        
        if ($rowsAffected > 0) {
            echo "<p style='color:green;'>✅ Licencia eliminada correctamente. Filas afectadas: $rowsAffected</p>";
            debugLog("Eliminación exitosa - Filas afectadas: $rowsAffected");
        } else {
            echo "<p style='color:orange;'>⚠️ No se encontró ninguna licencia con esa cuenta.</p>";
            debugLog("No se encontró la cuenta para eliminar");
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Error al eliminar: " . $e->getMessage() . "</p>";
        debugLog("Error en eliminación: " . $e->getMessage());
    }
}

// ===========================================
// PROCESAMIENTO DE ACTUALIZACIÓN
// ===========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cuenta_original'])) {
    echo "<h3>🔄 DEBUG DE ACTUALIZACIÓN</h3>";
    
    try {
        // Mostrar datos recibidos
        debugLog("Datos POST recibidos:");
        foreach ($_POST as $key => $value) {
            echo "- $key: $value<br>";
        }
        
        // Verificar que la cuenta original existe
        $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM licencias WHERE cuenta = :cuenta');
        $checkStmt->execute([':cuenta' => $_POST['cuenta_original']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        debugLog("¿Existe la cuenta original '{$_POST['cuenta_original']}'? " . ($exists ? 'SÍ' : 'NO'));
        
        if ($exists == 0) {
            echo "<p style='color:red;'>❌ Error: No se encontró la cuenta original '{$_POST['cuenta_original']}'</p>";
        } else {
            // Proceder con la actualización
            $updateSQL = 'UPDATE licencias SET 
                         cuenta=:cuenta, 
                         tipo=:tipo, 
                         expira=:expira, 
                         max_posiciones=:max_posiciones,
                         email=:email, 
                         comentario=:comentario, 
                         estado=:estado, 
                         version_permitida=:version_permitida
                         WHERE cuenta=:cuenta_original';
            
            debugLog("SQL a ejecutar: $updateSQL");
            
            $stmt = $db->prepare($updateSQL);
            $params = [
                ':cuenta_original' => $_POST['cuenta_original'],
                ':cuenta' => $_POST['cuenta'],
                ':tipo' => $_POST['tipo'],
                ':expira' => $_POST['expira'],
                ':max_posiciones' => $_POST['max_posiciones'],
                ':email' => $_POST['email'],
                ':comentario' => $_POST['comentario'],
                ':estado' => $_POST['estado'],
                ':version_permitida' => $_POST['version_permitida']
            ];
            
            debugLog("Parámetros de actualización:");
            foreach ($params as $key => $value) {
                echo "- $key: '$value'<br>";
            }
            
            $result = $stmt->execute($params);
            $rowsAffected = $stmt->rowCount();
            
            if ($result && $rowsAffected > 0) {
                echo "<p style='color:green;'>✅ Licencia actualizada correctamente. Filas afectadas: $rowsAffected</p>";
                debugLog("Actualización exitosa - Filas afectadas: $rowsAffected");
                
                // Verificar el cambio
                $verifyStmt = $db->prepare('SELECT * FROM licencias WHERE cuenta = :cuenta');
                $verifyStmt->execute([':cuenta' => $_POST['cuenta']]);
                $updatedRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($updatedRow) {
                    debugLog("Datos después de la actualización:");
                    foreach ($updatedRow as $col => $val) {
                        echo "- $col: '$val'<br>";
                    }
                }
            } else {
                echo "<p style='color:orange;'>⚠️ La actualización no afectó ninguna fila. Posibles causas:</p>";
                echo "<ul>";
                echo "<li>Los datos no cambiaron (son iguales a los existentes)</li>";
                echo "<li>La cuenta original no existe</li>";
                echo "<li>Error en la consulta SQL</li>";
                echo "</ul>";
                debugLog("UPDATE ejecutado pero sin filas afectadas");
            }
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Error en la actualización: " . $e->getMessage() . "</p>";
        debugLog("Error PDO en actualización: " . $e->getMessage());
    }
    
    echo "<hr>";
}

// ===========================================
// SECCIÓN DE DEBUG DE PRUEBA (OPCIONAL)
// ===========================================
if (isset($_GET['debug_test']) && $_GET['debug_test'] == '1') {
    echo "<h3>🧪 TEST DE ACTUALIZACIÓN ESPECÍFICA</h3>";
    
    try {
        // Cambia estos valores por los que necesites probar
        $test_id = 1; // Cambia por un ID que exista
        $nueva_fecha = '2025-07-30';
        
        debugLog("Probando actualización directa por ID");
        
        // Verificar si existe el registro
        $checkStmt = $db->prepare("SELECT * FROM licencias WHERE id = :id");
        $checkStmt->execute([':id' => $test_id]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            debugLog("Registro encontrado para ID $test_id:");
            foreach ($existing as $col => $val) {
                echo "- $col: '$val'<br>";
            }
            
            // Intentar actualizar usando la columna correcta 'expira'
            $stmt = $db->prepare("UPDATE licencias SET expira = :fecha WHERE id = :id");
            $success = $stmt->execute([':fecha' => $nueva_fecha, ':id' => $test_id]);
            $rowsAffected = $stmt->rowCount();
            
            if ($success && $rowsAffected > 0) {
                echo "✅ TEST UPDATE ejecutado con éxito. Filas afectadas: $rowsAffected<br>";
                
                // Confirmación de cambio
                $result = $db->query("SELECT id, expira FROM licencias WHERE id = $test_id");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                echo "🗓️ Fecha de expiración actual de ID $test_id: " . $row['expira'] . "<br>";
            } else {
                echo "⚠️ TEST UPDATE no aplicó cambios.<br>";
            }
        } else {
            debugLog("No se encontró registro con ID $test_id");
        }
        
    } catch (PDOException $e) {
        echo "❌ Error en test de actualización: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// ===========================================
// OBTENER Y MOSTRAR LICENCIAS
// ===========================================
try {
    $licencias = $db->query('SELECT * FROM licencias ORDER BY cuenta')->fetchAll(PDO::FETCH_ASSOC);
    debugLog("Se encontraron " . count($licencias) . " licencias en total");
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error al obtener licencias: " . $e->getMessage() . "</p>";
    $licencias = [];
}
?>

<h2>Panel de Licencias</h2>

<?php if (count($licencias) > 0): ?>
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
<tr style="background-color: #f0f0f0;">
<th>Cuenta</th><th>Tipo</th><th>Expira</th><th>Máx Pos.</th><th>Email</th><th>Comentario</th><th>Estado</th><th>Versión</th><th>Acción</th>
</tr>
<?php foreach ($licencias as $lic): ?>
<tr>
<form method="POST" onsubmit="return confirm('¿Confirmas la actualización de esta licencia?')">
<td><input name="cuenta" value="<?= htmlspecialchars($lic['cuenta'] ?? '') ?>" required></td>
<td>
    <select name="tipo" required>
        <option value="DEMO" <?= ($lic['tipo'] ?? '') == 'DEMO' ? 'selected' : '' ?>>DEMO</option>
        <option value="BASICO" <?= ($lic['tipo'] ?? '') == 'BASICO' ? 'selected' : '' ?>>BASICO</option>
        <option value="VIP" <?= ($lic['tipo'] ?? '') == 'VIP' ? 'selected' : '' ?>>VIP</option>
    </select>
</td>
<td><input type="date" name="expira" value="<?= htmlspecialchars($lic['expira'] ?? '') ?>" required></td>
<td><input name="max_posiciones" type="number" value="<?= htmlspecialchars($lic['max_posiciones'] ?? '') ?>" min="0" required></td>
<td><input name="email" type="email" value="<?= htmlspecialchars($lic['email'] ?? '') ?>"></td>
<td><input name="comentario" value="<?= htmlspecialchars($lic['comentario'] ?? '') ?>"></td>
<td>
    <select name="estado" required>
        <option value="activo" <?= ($lic['estado'] ?? '') == 'activo' ? 'selected' : '' ?>>activo</option>
        <option value="inactivo" <?= ($lic['estado'] ?? '') == 'inactivo' ? 'selected' : '' ?>>inactivo</option>
    </select>
</td>
<td><input name="version_permitida" value="<?= htmlspecialchars($lic['version_permitida'] ?? '') ?>"></td>
<td>
    <input type="hidden" name="cuenta_original" value="<?= htmlspecialchars($lic['cuenta'] ?? '') ?>">
    <button type="submit" style="background-color: #4CAF50; color: white; padding: 5px 10px; border: none; cursor: pointer;">Guardar</button>
    <a href="?eliminar=<?= urlencode($lic['cuenta'] ?? '') ?>" 
       onclick="return confirm('¿Estás seguro de eliminar la licencia de <?= htmlspecialchars($lic['cuenta'] ?? '') ?>?')"
       style="background-color: #f44336; color: white; padding: 5px 10px; text-decoration: none; margin-left: 5px;">Eliminar</a>
</td>
</form>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p style="color: orange;">⚠️ No se encontraron licencias en la base de datos.</p>
<?php endif; ?>

<div style="margin-top: 20px; padding: 10px; background-color: #e7f3ff; border: 1px solid #bee5eb;">
    <h4>🔧 Herramientas de Debug:</h4>
    <a href="?debug_test=1" style="background-color: #17a2b8; color: white; padding: 5px 10px; text-decoration: none;">Ejecutar Test de Actualización</a>
</div>

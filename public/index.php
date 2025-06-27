<?php
header('Content-Type: application/json');

// Ruta corregida y consistente con la estructura del proyecto
$log_file = "/app/teveo/log_accesos.txt";

// Función para obtener la IP real del cliente
function obtener_ip_real() {
    // Lista de headers que pueden contener la IP real
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_REAL_IP',            // Nginx proxy
        'HTTP_X_FORWARDED_FOR',      // Proxy estándar
        'HTTP_X_FORWARDED',          // Proxy alternativo
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster/Load balancer
        'HTTP_FORWARDED_FOR',        // RFC 7239
        'HTTP_FORWARDED',            // RFC 7239
        'REMOTE_ADDR'                // IP directa
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Si hay múltiples IPs (separadas por coma), tomar la primera
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validar que sea una IP válida y no privada/local
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            
            // Si es una IP válida aunque sea privada, la usamos (para desarrollo)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    // Fallback
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

// Función mejorada para guardar logs con manejo de errores y debugging
function registrar_log($cuenta, $broker, $version, $estado) {
    global $log_file;
    
    try {
        // Crear el directorio si no existe
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            $created = mkdir($log_dir, 0777, true);
            if (!$created) {
                error_log("ERROR: No se pudo crear el directorio: $log_dir");
                return false;
            }
            // Cambiar permisos después de crear
            chmod($log_dir, 0777);
        }
        
        // Verificar permisos del directorio
        if (!is_writable($log_dir)) {
            // Intentar cambiar permisos
            chmod($log_dir, 0777);
            if (!is_writable($log_dir)) {
                error_log("ERROR: No se puede escribir en el directorio de logs: $log_dir");
                return false;
            }
        }
        
        // Preparar la línea de log
        $ip = obtener_ip_real();
        $fecha = date('Y-m-d H:i:s');
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $linea = "[$fecha] IP: $ip | Cuenta: $cuenta | Broker: $broker | Versión: $version | Estado: $estado | Agent: $user_agent\n";
        
        // Intentar escribir
        $resultado = file_put_contents($log_file, $linea, FILE_APPEND | LOCK_EX);
        
        if ($resultado === false) {
            // Si falla, intentar sin LOCK_EX
            $resultado = file_put_contents($log_file, $linea, FILE_APPEND);
            if ($resultado === false) {
                error_log("ERROR: No se pudo escribir en el archivo de log: $log_file");
                // Intentar escribir en un archivo temporal para debugging
                $temp_log = "/tmp/debug_log_" . date('Y-m-d') . ".txt";
                file_put_contents($temp_log, "ERROR LOG: $linea", FILE_APPEND);
                return false;
            }
        }
        
        // Establecer permisos del archivo después de escribir
        chmod($log_file, 0666);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Excepción al registrar log: " . $e->getMessage());
        // Log de debugging
        $temp_log = "/tmp/debug_error_" . date('Y-m-d') . ".txt";
        file_put_contents($temp_log, "EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// ⚠️ Advertencia por acceso directo sin parámetros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['account'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2 style='color:red; font-family:sans-serif;'>⚠️ Este acceso ha sido registrado</h2>";
    echo "<p style='font-family:sans-serif;'>Intentar acceder directamente a este sistema es considerado una violación grave a la política del usuario.</p>";
    echo "<p style='font-family:sans-serif;'>Tu IP ha sido registrada y la cuenta asociada será bloqueada por uso indebido.</p>";

    // Registrar intento directo con manejo de errores mejorado
    try {
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
            chmod($log_dir, 0777);
        }
        
        $ip = obtener_ip_real();
        $fecha = date('Y-m-d H:i:s');
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $linea = "[$fecha] INTENTO DIRECTO | IP: $ip | Agent: $agent\n";
        
        $resultado = file_put_contents($log_file, $linea, FILE_APPEND | LOCK_EX);
        if ($resultado === false) {
            // Intentar sin LOCK_EX
            file_put_contents($log_file, $linea, FILE_APPEND);
        }
        // Establecer permisos después de escribir
        chmod($log_file, 0666);
        
    } catch (Exception $e) {
        error_log("Error al registrar intento directo: " . $e->getMessage());
        // Log temporal para debugging
        file_put_contents("/tmp/debug_direct_" . date('Y-m-d') . ".txt", 
                         "ERROR DIRECTO: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    exit;
}

// Soporte para POST y GET
$cuenta  = $_POST['account'] ?? $_GET['account'] ?? '';
$broker  = $_POST['broker'] ?? $_GET['broker'] ?? '';
$version = $_POST['ea_version'] ?? $_GET['ea_version'] ?? '';

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
    registrar_log($cuenta, $broker, $version, "Error interno: " . $e->getMessage());
    echo json_encode(["ok" => false, "error" => "Error interno"]);
}
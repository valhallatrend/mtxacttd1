<?php
echo "<h2>🔍 Análisis Detallado de la Respuesta del Servidor</h2>\n";

$url = 'https://axslsp.onrender.com/asxp.php';

echo "<strong>📡 Haciendo petición a:</strong> <code>$url</code><br><br>\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true, // Incluir headers en la respuesta
    CURLOPT_USERAGENT => 'DebugTool/1.0',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json, text/html, */*',
        'Cache-Control: no-cache'
    ]
]);

$start_time = microtime(true);
$full_response = curl_exec($ch);
$end_time = microtime(true);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
$curl_error = curl_error($ch);
$curl_errno = curl_errno($ch);

curl_close($ch);

// Separar headers y body
$headers = '';
$body = '';
if ($full_response && $header_size > 0) {
    $headers = substr($full_response, 0, $header_size);
    $body = substr($full_response, $header_size);
} else {
    $body = $full_response;
}

echo "<strong>⏱️ Tiempo de respuesta:</strong> " . round(($end_time - $start_time) * 1000, 2) . " ms<br>\n";
echo "<strong>📊 Código HTTP:</strong> <span style='color:" . ($http_code == 200 ? 'green' : 'red') . "; font-weight:bold;'>$http_code</span><br>\n";
echo "<strong>🔗 Tiempo cURL:</strong> $total_time segundos<br>\n";

if ($curl_errno) {
    echo "<strong>❌ Error cURL:</strong> [$curl_errno] $curl_error<br>\n";
}

echo "<br><strong>📋 HEADERS RECIBIDOS:</strong><br>\n";
if ($headers) {
    echo "<pre style='background:#f8f9fa; padding:10px; font-size:12px; border:1px solid #dee2e6; border-radius:5px; max-height:200px; overflow:auto;'>" . htmlspecialchars($headers) . "</pre><br>\n";
} else {
    echo "<em>No se pudieron extraer los headers</em><br><br>\n";
}

echo "<strong>📄 CONTENIDO COMPLETO DE LA RESPUESTA:</strong><br>\n";
echo "<strong>Longitud:</strong> " . strlen($body) . " caracteres<br><br>\n";

if (empty($body)) {
    echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "❌ <strong>RESPUESTA VACÍA</strong> - El servidor no devolvió contenido";
    echo "</div>";
} else {
    echo "<div style='border:1px solid #dee2e6; border-radius:5px; margin:10px 0;'>";
    echo "<div style='background:#e9ecef; padding:10px; border-bottom:1px solid #dee2e6;'>";
    echo "<strong>📝 Contenido completo:</strong>";
    echo "</div>";
    echo "<pre style='padding:15px; font-size:12px; max-height:400px; overflow:auto; margin:0;'>" . htmlspecialchars($body) . "</pre>";
    echo "</div>";
    
    // Análisis del contenido
    echo "<br><strong>🔍 ANÁLISIS DEL CONTENIDO:</strong><br><br>\n";
    
    // Verificar si es HTML
    if (stripos($body, '<html') !== false || stripos($body, '<!doctype') !== false) {
        echo "🎯 <strong style='color:red;'>PROBLEMA IDENTIFICADO:</strong> El servidor está devolviendo HTML en lugar de JSON<br>\n";
        echo "📄 <strong>Tipo de contenido:</strong> Página HTML (probablemente error 502 de Render)<br>\n";
        
        // Extraer título si es HTML
        if (preg_match('/<title>(.*?)<\/title>/i', $body, $matches)) {
            echo "📋 <strong>Título de la página:</strong> " . htmlspecialchars($matches[1]) . "<br>\n";
        }
        
        // Buscar mensajes de error comunes
        $error_patterns = [
            'Application Error' => 'Error de aplicación',
            '502 Bad Gateway' => 'Error 502 - Gateway malo',
            'Service Unavailable' => 'Servicio no disponible',
            'Internal Server Error' => 'Error interno del servidor',
            'render.com' => 'Página de error de Render.com'
        ];
        
        foreach ($error_patterns as $pattern => $description) {
            if (stripos($body, $pattern) !== false) {
                echo "⚠️ <strong>Detectado:</strong> $description<br>\n";
            }
        }
        
    } elseif (stripos(trim($body), '{') === 0 || stripos(trim($body), '[') === 0) {
        echo "🎯 <strong>El contenido parece ser JSON</strong><br>\n";
        
        // Intentar decodificar
        $json_data = json_decode($body, true);
        $json_error = json_last_error();
        
        if ($json_error === JSON_ERROR_NONE) {
            echo "✅ <strong style='color:green;'>JSON válido!</strong><br>\n";
            echo "📊 Tipo: " . gettype($json_data) . "<br>\n";
            if (is_array($json_data)) {
                echo "📋 Elementos: " . count($json_data) . "<br>\n";
            }
        } else {
            echo "❌ <strong style='color:red;'>JSON inválido:</strong> " . json_last_error_msg() . "<br>\n";
            
            // Análisis de caracteres problemáticos
            $clean_body = trim($body);
            echo "📝 <strong>Primeros/últimos caracteres:</strong><br>\n";
            echo "Primeros 50: <code>" . htmlspecialchars(substr($clean_body, 0, 50)) . "</code><br>\n";
            echo "Últimos 50: <code>" . htmlspecialchars(substr($clean_body, -50)) . "</code><br>\n";
            
            // Buscar caracteres no ASCII
            $problematic_chars = [];
            for ($i = 0; $i < min(strlen($clean_body), 200); $i++) {
                $char = $clean_body[$i];
                $ord = ord($char);
                if ($ord < 32 || $ord > 126) {
                    $problematic_chars[] = "Pos $i: '" . addslashes($char) . "' (ASCII: $ord)";
                }
            }
            
            if (!empty($problematic_chars)) {
                echo "⚠️ <strong>Caracteres problemáticos encontrados:</strong><br>\n";
                foreach (array_slice($problematic_chars, 0, 5) as $char_info) {
                    echo "  - $char_info<br>\n";
                }
            }
        }
        
    } else {
        echo "🎯 <strong>Contenido de tipo desconocido</strong><br>\n";
        echo "📄 No parece ser ni HTML ni JSON<br>\n";
        echo "🔤 <strong>Primeros 100 caracteres:</strong><br>\n";
        echo "<code>" . htmlspecialchars(substr($body, 0, 100)) . "</code><br>\n";
    }
}

echo "<br><strong>💡 RECOMENDACIONES BASADAS EN EL ANÁLISIS:</strong><br><br>\n";

if ($http_code == 502) {
    echo "<div style='background:#fff3cd; padding:15px; border:1px solid #ffeaa7; border-radius:5px;'>";
    echo "🎯 <strong>PROBLEMA CONFIRMADO:</strong> Error 502 Bad Gateway<br><br>";
    echo "<strong>📋 Esto significa:</strong><br>";
    echo "• El servidor axslsp.onrender.com está fallando internamente<br>";
    echo "• Probablemente hay un error en el código PHP de asxp.php<br>";
    echo "• O el servicio está sobrecargado/mal configurado<br><br>";
    echo "<strong>✅ SOLUCIONES:</strong><br>";
    echo "1. <strong>Contactar al administrador</strong> de axslsp.onrender.com<br>";
    echo "2. <strong>Implementar sistema de autenticación local</strong> como respaldo<br>";
    echo "3. <strong>Usar credenciales hardcodeadas</strong> temporalmente<br>";
    echo "</div>";
    
} elseif ($http_code == 200 && !empty($body)) {
    if (stripos($body, '<html') !== false) {
        echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
        echo "🎯 <strong>PROBLEMA IDENTIFICADO:</strong> El servidor devuelve HTML en lugar de JSON<br><br>";
        echo "<strong>✅ SOLUCIÓN:</strong> Revisar el archivo asxp.php - probablemente tiene errores que hacen que devuelva HTML de error";
        echo "</div>";
    } else {
        echo "<div style='background:#d4edda; padding:15px; border:1px solid #c3e6cb; border-radius:5px;'>";
        echo "✅ <strong>El servidor responde correctamente</strong><br>";
        echo "🔧 El problema puede ser intermitente o de parsing JSON";
        echo "</div>";
    }
} else {
    echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "❌ <strong>SERVIDOR NO DISPONIBLE</strong><br>";
    echo "🔧 Implementar sistema de respaldo es necesario";
    echo "</div>";
}

// Botón para refrescar
echo "<br><div style='margin-top:20px;'>";
echo "<a href='" . $_SERVER['PHP_SELF'] . "' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Actualizar análisis</a> ";
echo "<a href='panel_temp.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; margin-left:10px;'>📋 Ir al panel</a>";
echo "</div>";
?>

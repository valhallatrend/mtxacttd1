<?php
echo "<h2>🔄 Activador de Servicio de Autenticación</h2>";

if (isset($_GET['wake'])) {
    $url = 'https://axslsp.onrender.com/asxp.php';
    
    echo "<p>🔄 Enviando petición de activación a: <code>$url</code></p>";
    echo "<p>⏱️ Esto puede tomar 30-60 segundos para servicios dormidos...</p>";
    
    // Flush output para mostrar el progreso
    ob_flush();
    flush();
    
    $start_time = time();
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'ServiceWaker/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $end_time = time();
    $duration = $end_time - $start_time;
    
    echo "<hr>";
    echo "<h3>📊 Resultado:</h3>";
    echo "<strong>⏱️ Tiempo total:</strong> {$duration} segundos<br>";
    echo "<strong>📡 Código HTTP:</strong> $http_code<br>";
    echo "<strong>🔗 Tiempo cURL:</strong> " . round($total_time, 2) . " segundos<br>";
    
    if ($curl_error) {
        echo "<strong>❌ Error cURL:</strong> $curl_error<br>";
    }
    
    if ($http_code == 200) {
        echo "<div style='background:#d4edda; border:1px solid #c3e6cb; padding:15px; border-radius:5px; margin:10px 0;'>";
        echo "✅ <strong>¡Servicio activado correctamente!</strong><br>";
        echo "🎯 El servidor de autenticación está ahora disponible.<br>";
        echo "🔄 Puedes intentar acceder al panel de licencias.";
        echo "</div>";
        
        // Verificar que devuelve JSON válido
        if ($response) {
            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                echo "<strong>✅ JSON válido recibido</strong> (" . count($json) . " usuario(s))<br>";
            } else {
                echo "<strong>⚠️ Respuesta no es JSON válido:</strong> " . json_last_error_msg() . "<br>";
                echo "<strong>Respuesta recibida:</strong><br>";
                echo "<pre style='background:#f8f9fa; padding:10px; max-height:200px; overflow:auto;'>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        }
        
    } elseif ($http_code == 502) {
        echo "<div style='background:#f8d7da; border:1px solid #f5c6cb; padding:15px; border-radius:5px; margin:10px 0;'>";
        echo "❌ <strong>Servicio sigue dormido (HTTP 502)</strong><br>";
        echo "🔄 Los servicios gratuitos de Render pueden tardar más en despertar.<br>";
        echo "💡 <strong>Opciones:</strong><br>";
        echo "   1. Esperar 2-3 minutos más e intentar de nuevo<br>";
        echo "   2. Contactar al administrador del servicio de autenticación<br>";
        echo "   3. Usar el modo de emergencia en el sistema";
        echo "</div>";
        
    } else {
        echo "<div style='background:#fff3cd; border:1px solid #ffeaa7; padding:15px; border-radius:5px; margin:10px 0;'>";
        echo "⚠️ <strong>Respuesta inesperada (HTTP $http_code)</strong><br>";
        echo "🔍 El servicio puede estar experimentando problemas.<br>";
        if ($response) {
            echo "<strong>Respuesta del servidor:</strong><br>";
            echo "<pre style='background:#f8f9fa; padding:10px; max-height:200px; overflow:auto;'>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
        echo "</div>";
    }
    
    echo "<br><a href='wake_service.php'>🔄 Intentar nuevamente</a> | ";
    echo "<a href='panel.php'>📋 Ir al panel</a>";
    
} else {
    echo "<p>El servicio de autenticación parece estar dormido (error HTTP 502).</p>";
    echo "<p>Los servicios gratuitos de Render.com se duermen tras períodos de inactividad.</p>";
    echo "<p>🔽 <strong>Haz clic para despertar el servicio:</strong></p>";
    echo "<a href='?wake=1' style='display:inline-block; background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Despertar Servicio</a>";
    
    echo "<hr>";
    echo "<h3>📋 Información sobre errores HTTP 502:</h3>";
    echo "<ul>";
    echo "<li><strong>502 Bad Gateway:</strong> El servidor destino no puede procesar la petición</li>";
    echo "<li><strong>Causa común:</strong> Servicio dormido en plataformas gratuitas</li>";
    echo "<li><strong>Tiempo de activación:</strong> 30-60 segundos típicamente</li>";
    echo "<li><strong>Solución:</strong> Enviar una petición para despertar el servicio</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }
</style>

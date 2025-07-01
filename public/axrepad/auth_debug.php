<?php
echo "<h2>🔍 Diagnóstico Profundo del Sistema de Autenticación</h2>\n";

$url = 'https://axslsp.onrender.com/asxp.php';

echo "<strong>1. 📡 Información de la petición:</strong><br>\n";
echo "URL destino: <code>$url</code><br>\n";
echo "Método: GET<br>\n";
echo "Timeout configurado: 5 segundos<br><br>\n";

echo "<strong>2. 🌐 Test de conectividad básica:</strong><br>\n";

// Test 1: Verificar si la URL responde
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Aumentamos timeout para debug
curl_setopt($ch, CURLOPT_VERBOSE, false);
curl_setopt($ch, CURLOPT_HEADER, true); // Incluir headers en respuesta
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Por si hay problemas SSL
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirects
curl_setopt($ch, CURLOPT_USERAGENT, 'Auth-Debug/1.0'); // User agent personalizado

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
$curl_error = curl_error($ch);
$curl_errno = curl_errno($ch);

curl_close($ch);

echo "⏱️ Tiempo de respuesta: " . round(($end_time - $start_time) * 1000, 2) . " ms<br>\n";
echo "📊 Código HTTP: <strong>$http_code</strong><br>\n";
echo "🔗 cURL Info Total Time: $total_time segundos<br>\n";

if ($curl_errno !== 0) {
    echo "❌ <strong style='color:red;'>Error cURL:</strong> [$curl_errno] $curl_error<br>\n";
} else {
    echo "✅ <strong style='color:green;'>cURL exitoso</strong><br>\n";
}

echo "<br><strong>3. 📄 Análisis de la respuesta completa:</strong><br>\n";

if ($response === false) {
    echo "❌ <strong style='color:red;'>No se recibió respuesta</strong><br>\n";
} else {
    // Separar headers del body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    if ($header_size > 0) {
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
    } else {
        // Si no podemos separar, intentamos encontrar el doble salto de línea
        $split = explode("\r\n\r\n", $response, 2);
        if (count($split) == 2) {
            $headers = $split[0];
            $body = $split[1];
        } else {
            $headers = "No se pudieron extraer headers";
            $body = $response;
        }
    }
    
    echo "<strong>📋 Headers recibidos:</strong><br>\n";
    echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px; max-height:200px; overflow:auto;'>" . htmlspecialchars($headers) . "</pre><br>\n";
    
    echo "<strong>📝 Cuerpo de la respuesta:</strong><br>\n";
    echo "Longitud: " . strlen($body) . " caracteres<br>\n";
    echo "Primeros 500 caracteres:<br>\n";
    echo "<pre style='background:#f0f0f0; padding:10px; font-size:12px; max-height:300px; overflow:auto;'>" . htmlspecialchars(substr($body, 0, 500)) . "</pre><br>\n";
    
    if (strlen($body) > 500) {
        echo "Últimos 200 caracteres:<br>\n";
        echo "<pre style='background:#f0f0f0; padding:10px; font-size:12px;'>" . htmlspecialchars(substr($body, -200)) . "</pre><br>\n";
    }
}

echo "<strong>4. 🧪 Análisis JSON:</strong><br>\n";

if (isset($body)) {
    // Limpiar posibles caracteres invisibles
    $cleaned_body = trim($body);
    $cleaned_body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cleaned_body); // Remover caracteres no ASCII
    
    echo "Cuerpo limpio (sin caracteres especiales):<br>\n";
    echo "<pre style='background:#e8f4fd; padding:10px; font-size:12px;'>" . htmlspecialchars($cleaned_body) . "</pre><br>\n";
    
    // Intentar decodificar JSON
    $json_data = json_decode($cleaned_body, true);
    $json_error = json_last_error();
    
    if ($json_error === JSON_ERROR_NONE) {
        echo "✅ <strong style='color:green;'>JSON válido!</strong><br>\n";
        echo "Tipo de datos: " . gettype($json_data) . "<br>\n";
        
        if (is_array($json_data)) {
            echo "Número de elementos: " . count($json_data) . "<br>\n";
            echo "Estructura del JSON:<br>\n";
            echo "<pre style='background:#e8f5e8; padding:10px; font-size:12px; max-height:300px; overflow:auto;'>" . htmlspecialchars(print_r($json_data, true)) . "</pre><br>\n";
            
            // Verificar estructura esperada
            echo "<strong>🔍 Verificación de estructura:</strong><br>\n";
            if (empty($json_data)) {
                echo "⚠️ Array vacío<br>\n";
            } else {
                $first_element = reset($json_data);
                if (is_array($first_element)) {
                    if (isset($first_element['u']) && isset($first_element['p'])) {
                        echo "✅ Estructura correcta: elementos con 'u' y 'p'<br>\n";
                    } else {
                        echo "❌ Estructura incorrecta: faltan campos 'u' o 'p'<br>\n";
                        echo "Campos disponibles en primer elemento: " . implode(', ', array_keys($first_element)) . "<br>\n";
                    }
                } else {
                    echo "❌ Los elementos no son arrays<br>\n";
                    echo "Tipo del primer elemento: " . gettype($first_element) . "<br>\n";
                }
            }
        } else {
            echo "❌ La respuesta no es un array<br>\n";
        }
        
    } else {
        echo "❌ <strong style='color:red;'>JSON inválido!</strong><br>\n";
        echo "Error JSON: " . json_last_error_msg() . " (código: $json_error)<br>\n";
        
        // Análisis de caracteres problemáticos
        echo "<br><strong>🔍 Análisis de caracteres problemáticos:</strong><br>\n";
        $char_analysis = [];
        for ($i = 0; $i < min(strlen($cleaned_body), 100); $i++) {
            $char = $cleaned_body[$i];
            $ord = ord($char);
            if ($ord < 32 || $ord > 126) {
                $char_analysis[] = "Posición $i: '" . addslashes($char) . "' (ASCII: $ord)";
            }
        }
        
        if (empty($char_analysis)) {
            echo "✅ No se encontraron caracteres problemáticos en los primeros 100 caracteres<br>\n";
        } else {
            echo "⚠️ Caracteres problemáticos encontrados:<br>\n";
            foreach (array_slice($char_analysis, 0, 10) as $problem) {
                echo "  - $problem<br>\n";
            }
        }
    }
}

echo "<br><strong>5. 🔧 Recomendaciones:</strong><br>\n";

if (isset($http_code)) {
    if ($http_code == 200) {
        if (isset($json_error) && $json_error !== JSON_ERROR_NONE) {
            echo "🎯 <strong>Problema identificado:</strong> El servidor responde pero no devuelve JSON válido<br>\n";
            echo "💡 <strong>Soluciones:</strong><br>\n";
            echo "   1. Verificar que el endpoint /asxp.php devuelve JSON puro (sin HTML/texto extra)<br>\n";
            echo "   2. Revisar si hay errores PHP en el servidor destino<br>\n";
            echo "   3. Verificar que no hay saltos de línea o espacios antes del JSON<br>\n";
        } else {
            echo "✅ <strong>Todo parece correcto</strong> - El problema podría ser intermitente<br>\n";
        }
    } else {
        echo "🎯 <strong>Problema identificado:</strong> El servidor no responde correctamente (HTTP $http_code)<br>\n";
        echo "💡 <strong>Soluciones:</strong><br>\n";
        echo "   1. Verificar que la URL esté activa<br>\n";
        echo "   2. Revisar si el servidor destino está funcionando<br>\n";
        echo "   3. Verificar configuración de DNS/proxy<br>\n";
    }
}

echo "<br><strong>6. 🧪 Test con configuración mejorada:</strong><br>\n";

// Segundo intento con configuración más robusta
$ch2 = curl_init($url);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AuthSystem/1.0)',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Cache-Control: no-cache'
    ]
]);

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch2);
curl_close($ch2);

echo "Segundo intento con configuración mejorada:<br>\n";
echo "Código HTTP: $http_code2<br>\n";
if ($error2) {
    echo "Error: $error2<br>\n";
} else {
    $json2 = json_decode($response2, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ <strong style='color:green;'>JSON válido en segundo intento!</strong><br>\n";
    } else {
        echo "❌ JSON sigue siendo inválido: " . json_last_error_msg() . "<br>\n";
    }
}
?>
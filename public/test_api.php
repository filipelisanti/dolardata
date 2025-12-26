<?php
// public/test_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de conexión a API</h1>";

// Test directo sin clases
$url = "https://dolarapi.com/v1/dolares";
echo "<h2>Probando URL: $url</h2>";

// Método 1: file_get_contents
echo "<h3>Método 1: file_get_contents</h3>";
try {
    $data = file_get_contents($url);
    if ($data === false) {
        echo "Error: No se pudo obtener datos<br>";
    } else {
        $json = json_decode($data, true);
        echo "<pre>" . print_r($json, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Método 2: cURL
echo "<h3>Método 2: cURL</h3>";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'Test/1.0',
    CURLOPT_SSL_VERIFYPEER => false, // Solo para test
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Error: " . ($error ?: 'Ninguno') . "<br>";

if ($response) {
    $data = json_decode($response, true);
    echo "<pre>" . print_r($data, true) . "</pre>";
}

// Test específico del dólar blue
echo "<h2>Test Dólar Blue específico</h2>";
$urlBlue = "https://dolarapi.com/v1/dolares/blue";
$dataBlue = file_get_contents($urlBlue);
echo "Respuesta:<br>";
echo "<pre>" . htmlspecialchars($dataBlue) . "</pre>";
?>
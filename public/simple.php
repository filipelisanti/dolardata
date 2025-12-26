<?php
// public/simple.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = "https://dolarapi.com/v1/dolares";
echo "<h1>Test Simple API</h1>";

// Método más simple
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
    'http' => [
        'timeout' => 10,
        'header' => "User-Agent: Mozilla/5.0\r\n"
    ]
]);

$data = @file_get_contents($url, false, $context);

if ($data === false) {
    echo "<p style='color: red;'>Error: No se pudo conectar a la API</p>";
    echo "<p>Posibles causas:</p>";
    echo "<ul>";
    echo "<li>Firewall bloqueando la conexión</li>";
    echo "<li>allow_url_fopen está desactivado en php.ini</li>";
    echo "<li>Problema de DNS</li>";
    echo "</ul>";
    
    // Verificar configuración PHP
    echo "<h2>Configuración PHP:</h2>";
    echo "<pre>";
    echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
    echo "openssl: " . (extension_loaded('openssl') ? 'Cargado' : 'No cargado');
    echo "</pre>";
} else {
    $json = json_decode($data, true);
    echo "<h2>Datos obtenidos:</h2>";
    echo "<pre>" . print_r($json, true) . "</pre>";
    
    echo "<h2>Valores formateados:</h2>";
    foreach ($json as $dolar) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
        echo "<h3>" . ($dolar['nombre'] ?? $dolar['casa']) . "</h3>";
        echo "<p>Compra: $" . number_format($dolar['compra'], 2, ',', '.') . "</p>";
        echo "<p>Venta: $" . number_format($dolar['venta'], 2, ',', '.') . "</p>";
        echo "</div>";
    }
}
?>
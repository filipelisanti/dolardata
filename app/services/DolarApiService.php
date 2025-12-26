<?php
// app/services/DolarApiService.php

class DolarApiService {
    private $baseUrl;
    private $cacheDir;
    private $cacheDuration;
    
    public function __construct() {
        $this->baseUrl = 'https://dolarapi.com/v1'; // URL directa
        $this->cacheDir = dirname(__DIR__, 2) . '/cache/api_responses/';
        $this->cacheDuration = 300; // 5 minutos en segundos
        
        // Crear directorio de cache si no existe
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Obtiene todas las cotizaciones disponibles
     */
    public function getAllCotizaciones() {
        $cacheKey = 'all_cotizaciones';
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $url = $this->baseUrl . '/dolares';
            $data = $this->makeRequest($url);
            
            // Verificar que tenemos datos válidos
            if (empty($data) || !is_array($data)) {
                throw new Exception("Respuesta vacía o inválida de la API");
            }
            
            // Guardar en cache
            $this->saveToCache($cacheKey, $data);
            
            return $data;
        } catch (Exception $e) {
            error_log("Error fetching all cotizaciones: " . $e->getMessage());
            return $this->getBackupData('all');
        }
    }
    
    /**
     * Obtiene cotización específica por tipo
     */
    public function getCotizacion($type = 'blue') {
        $cacheKey = 'cotizacion_' . $type;
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $url = $this->baseUrl . '/dolares/' . $type;
            $data = $this->makeRequest($url);
            
            // Verificar datos
            if (empty($data)) {
                throw new Exception("Respuesta vacía para tipo: " . $type);
            }
            
            // Guardar en cache
            $this->saveToCache($cacheKey, $data);
            
            return $data;
        } catch (Exception $e) {
            error_log("Error fetching cotizacion {$type}: " . $e->getMessage());
            return $this->getBackupData($type);
        }
    }
    
    /**
     * Obtiene múltiples cotizaciones específicas
     */
    public function getMultipleCotizaciones(array $types) {
        $results = [];
        
        foreach ($types as $type) {
            $results[$type] = $this->getCotizacion($type);
        }
        
        return $results;
    }
    
    /**
     * Obtiene cotizaciones de otras monedas
     */
    public function getOtrasMonedas() {
        $cacheKey = 'otras_monedas';
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $url = $this->baseUrl . '/cotizaciones';
            $data = $this->makeRequest($url);
            
            if (empty($data)) {
                return [];
            }
            
            // Filtrar solo las que no son dólares
            $otrasMonedas = array_filter($data, function($item) {
                $nombre = strtolower($item['nombre'] ?? '');
                return strpos($nombre, 'dolar') === false && 
                       strpos($nombre, 'dólar') === false;
            });
            
            $this->saveToCache($cacheKey, $otrasMonedas);
            
            return $otrasMonedas;
        } catch (Exception $e) {
            error_log("Error fetching otras monedas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Realiza una petición HTTP - VERSIÓN MEJORADA
     */
    private function makeRequest($url) {
        // Opción 1: Usar file_get_contents con contexto (como en simple.php)
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: DolarArgentinaApp/1.0\r\n" .
                           "Accept: application/json\r\n"
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            // Si file_get_contents falla, intentar con cURL
            return $this->makeRequestCurl($url);
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Parse Error: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Método alternativo con cURL
     */
    private function makeRequestCurl($url) {
        if (!function_exists('curl_init')) {
            throw new Exception("cURL no está disponible en este servidor");
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'DolarArgentinaApp/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error $httpCode para URL: $url");
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Parse Error: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Sistema de cache simple
     */
    private function getFromCache($key) {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $fileTime = filemtime($cacheFile);
        if (time() - $fileTime > $this->cacheDuration) {
            return false; // Cache expirado
        }
        
        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);
        
        return $data['data'] ?? false;
    }
    
    private function saveToCache($key, $data) {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        $cacheData = [
            'data' => $data,
            'timestamp' => time(),
            'expires' => time() + $this->cacheDuration,
            'created' => date('Y-m-d H:i:s')
        ];
        file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Datos de respaldo por si falla la API
     */
    private function getBackupData($type = 'all') {
        $backupData = [
            'blue' => [
                'compra' => 980.50,
                'venta' => 1020.75,
                'casa' => 'blue',
                'nombre' => 'Dólar Blue',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ],
            'oficial' => [
                'compra' => 850.25,
                'venta' => 890.50,
                'casa' => 'oficial',
                'nombre' => 'Dólar Oficial',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ],
            'bolsa' => [
                'compra' => 960.80,
                'venta' => 990.25,
                'casa' => 'bolsa',
                'nombre' => 'Dólar Bolsa',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ],
            'contadoconliqui' => [
                'compra' => 970.40,
                'venta' => 1005.60,
                'casa' => 'contadoconliqui',
                'nombre' => 'Dólar Contado con Liqui',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ],
            'mayorista' => [
                'compra' => 848.75,
                'venta' => 855.25,
                'casa' => 'mayorista',
                'nombre' => 'Dólar Mayorista',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ],
            'tarjeta' => [
                'compra' => 1450.80,
                'venta' => 1500.25,
                'casa' => 'tarjeta',
                'nombre' => 'Dólar Tarjeta',
                'moneda' => 'USD',
                'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
            ]
        ];
        
        if ($type === 'all') {
            return array_values($backupData);
        }
        
        return $backupData[$type] ?? $backupData['blue'];
    }
    
    /**
     * Limpia el cache
     */
    public function clearCache() {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return count($files);       
    }
    
    /**
     * Obtiene estadísticas del cache
     */
    public function getCacheStats() {
        $files = glob($this->cacheDir . '*.json');
        $stats = [
            'total_files' => count($files),
            'files' => []
        ];
        
        foreach ($files as $file) {
            $stats['files'][] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        return $stats;
    }
}
?>
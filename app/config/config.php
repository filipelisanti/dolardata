<?php
// app/config/config.php

// Activar errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Config {
    private static $config = [];
    
    public static function load() {
        // Cargar .env si existe
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
        
        // Configuración por defecto
        self::$config = array_merge([
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'dolar_argentina',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'API_BASE_URL' => 'https://dolarapi.com/v1',
            'TIMEZONE' => 'America/Argentina/Buenos_Aires',
            'CACHE_DURATION' => 300,
            'ENVIRONMENT' => 'development'
        ], self::$config);
        
        // Configurar timezone
        date_default_timezone_set(self::get('TIMEZONE'));
    }
    
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
}

// Cargar configuración
Config::load();
?>
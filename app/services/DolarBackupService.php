<?php
// app/services/DolarBackupService.php

class DolarBackupService {
    private static $backupData = [
        'blue' => [
            'compra' => 980,
            'venta' => 1020,
            'casa' => 'blue',
            'nombre' => 'Dólar Blue',
            'moneda' => 'USD',
            'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
        ],
        'oficial' => [
            'compra' => 850,
            'venta' => 890,
            'casa' => 'oficial',
            'nombre' => 'Dólar Oficial',
            'moneda' => 'USD',
            'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
        ],
        'bolsa' => [
            'compra' => 960,
            'venta' => 990,
            'casa' => 'bolsa',
            'nombre' => 'Dólar Bolsa',
            'moneda' => 'USD',  
            'fechaActualizacion' => date('Y-m-d\TH:i:s.v\Z')
        ]
    ];
    
    public static function getBackupData($type = 'all') {
        if ($type === 'all') {
            return array_values(self::$backupData);
        }
        
        return self::$backupData[$type] ?? self::$backupData['blue'];
    }
}
?>
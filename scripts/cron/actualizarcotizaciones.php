<?php
// scripts/cron/actualizar_cotizaciones.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/DolarApiService.php';
require_once __DIR__ . '/../../app/models/CotizacionModel.php';

$api = new DolarApiService();
$model = new CotizacionModel();

// Tipos a actualizar
$tipos = ['oficial', 'blue', 'bolsa', 'contadoconliqui', 'tarjeta', 'mayorista'];
$actualizados = 0;

foreach ($tipos as $tipo) {
    if ($model->necesitaActualizar($tipo, 5)) {
        $data = $api->getCotizacion($tipo);
        
        if ($data) {
            $data['tipo_dolar'] = $tipo;
            $model->guardarCotizacion($data);
            $actualizados++;
            
            echo "Actualizado: {$tipo} - {$data['venta']}\n";
        }
    }
}

// Limpiar registros antiguos (una vez al día)
if (date('H') == '00') {
    $model->limpiarRegistrosAntiguos();
    echo "Registros antiguos limpiados.\n";
}

echo "Total actualizados: {$actualizados}\n";
?>
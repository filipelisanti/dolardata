<?php
// public/historico.php

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';
require_once dirname(__DIR__) . '/app/models/CotizacionModel.php';

$model = new CotizacionModel();
$tipo = $_GET['tipo'] ?? 'blue';
$dias = $_GET['dias'] ?? 30;

$historico = $model->getHistorico($tipo, $dias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Histórico - Dólar <?php echo ucfirst($tipo); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reutiliza los estilos del index o crea unos básicos */
        body { font-family: Arial, sans-serif; margin: 20px; }
        .back-btn { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #2c3e50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn">← Volver al inicio</a>
    <h1>Histórico - Dólar <?php echo ucfirst($tipo); ?></h1>
    
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Compra Promedio</th>
                <th>Venta Promedio</th>
                <th>Compra Mínima</th>
                <th>Compra Máxima</th>
                <th>Venta Mínima</th>
                <th>Venta Máxima</th>
                <th>Registros</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historico as $registro): ?>
            <tr>
                <td><?php echo $registro['fecha']; ?></td>
                <td>$<?php echo number_format($registro['compra_promedio'], 2, ',', '.'); ?></td>
                <td>$<?php echo number_format($registro['venta_promedio'], 2, ',', '.'); ?></td>
                <td>$<?php echo number_format($registro['compra_min'], 2, ',', '.'); ?></td>
                <td>$<?php echo number_format($registro['compra_max'], 2, ',', '.'); ?></td>
                <td>$<?php echo number_format($registro['venta_min'], 2, ',', '.'); ?></td>
                <td>$<?php echo number_format($registro['venta_max'], 2, ',', '.'); ?></td>
                <td><?php echo $registro['registros']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
// public/index.php - VERSIÓN FINAL CON RUTA CSS CORREGIDA

// Activar errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir ruta raíz
define('ROOT_DIR', dirname(__DIR__));

// Cargar configuración
require_once ROOT_DIR . '/app/config/config.php';

// Cargar servicios
require_once ROOT_DIR . '/app/services/DolarApiService.php';

/**
 * Función para formatear nombres de tipos de dólar
 */
function formatearNombreDolar($nombre) {
    $formateos = [
        'contadoconliqui' => 'Contado con Liqui',
        'mayorista' => 'Mayorista',
        'tarjeta' => 'Tarjeta',
        'bolsa' => 'Bolsa',
        'blue' => 'Blue',
        'oficial' => 'Oficial'
    ];
    
    foreach ($formateos as $key => $value) {
        if (stripos($nombre, $key) !== false) {
            return $value;
        }
    }
    
    return $nombre;
}

/**
 * Función para formatear nombres completos (de la API)
 */
function formatearNombreCompleto($nombre) {
    $formateos = [
        'Dólar Contado con Liqui' => 'Contado con Liqui',
        'Dólar Contadoconliqui' => 'Contado con Liqui',
        'Dólar Mayorista' => 'Mayorista',
        'Dólar Tarjeta' => 'Tarjeta',
        'Dólar Bolsa' => 'Bolsa',
        'Dólar Blue' => 'Blue',
        'Dólar Oficial' => 'Oficial',
        'Contadoconliqui' => 'Contado con Liqui'
    ];
    
    foreach ($formateos as $original => $formateado) {
        if ($nombre === $original || stripos($nombre, $original) !== false) {
            return $formateado;
        }
    }
    
    return $nombre;
}

// Intentar cargar base de datos (opcional)
$dbAvailable = false;
try {
    require_once ROOT_DIR . '/app/config/database.php';
    require_once ROOT_DIR . '/app/models/CotizacionModel.php';
    $cotizacionModel = new CotizacionModel();
    $dbAvailable = true;
} catch (Exception $e) {
    error_log("BD no disponible: " . $e->getMessage());
    $dbAvailable = false;
}

// Inicializar API
$dolarApi = new DolarApiService();

// Configurar tipo de dólar
$tipo = $_GET['tipo'] ?? 'blue';
$tiposPermitidos = ['oficial', 'blue', 'bolsa', 'contadoconliqui', 'tarjeta', 'mayorista'];
if (!in_array($tipo, $tiposPermitidos)) {
    $tipo = 'blue';
}

// Obtener datos de la API
$cotizacion = $dolarApi->getCotizacion($tipo);
$todasCotizaciones = $dolarApi->getAllCotizaciones();
$otrasMonedas = $dolarApi->getOtrasMonedas();

// Procesar datos para mostrar
$mostrarDatosReales = !empty($todasCotizaciones);
$ultimaActualizacion = $cotizacion['fechaActualizacion'] ?? date('Y-m-d H:i:s');

// Si hay base de datos, guardar datos
if ($dbAvailable && isset($_GET['guardar']) && $_GET['guardar'] == '1' && $cotizacion) {
    try {
        $datosParaGuardar = $cotizacion;
        $datosParaGuardar['tipo_dolar'] = $tipo;
        $cotizacionModel->guardarCotizacion($datosParaGuardar);
    } catch (Exception $e) {
        error_log("Error guardando: " . $e->getMessage());
    }
}

// Obtener estadísticas si hay BD
$estadisticasDia = [];
$ultimaGuardada = null;
if ($dbAvailable) {
    try {
        $estadisticasDia = $cotizacionModel->getEstadisticasDia();
        $ultimaGuardada = $cotizacionModel->getUltimaCotizacion($tipo);
    } catch (Exception $e) {
        // Silenciar error
    }
}

// Calcular variación
$variacion = 0;
if ($ultimaGuardada && isset($cotizacion['venta']) && $ultimaGuardada['venta'] > 0) {
    $variacion = (($cotizacion['venta'] - $ultimaGuardada['venta']) / $ultimaGuardada['venta']) * 100;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dólar en Argentina - Cotizaciones en Tiempo Real</title>
    <!-- RUTA CSS CORREGIDA: ../assets/css/styles.css -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos adicionales solo para esta página */
        .data-source {
            text-align: center;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 0.95rem;
            border-left: 4px solid transparent;
        }
        
        .data-source.real {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .data-source.backup {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .variacion {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .variacion-positiva {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .variacion-negativa {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .current-time {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .save-btn {
            display: inline-block;
            background: var(--success-color);
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .save-btn:hover {
            background: #219653;
            transform: translateY(-2px);
        }
        
        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 30px;
            font-style: italic;
        }
        
        .no-data i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    Dólar Argentina
                    <span class="api-badge">API: dolarapi.com</span>
                </div>
                <div class="last-update">
                    <i class="fas fa-sync-alt"></i>
                    Actualizado cada 5 minutos
                    <div class="current-time" id="current-time"></div>
                </div>
            </div>
            
           <div class="nav-tabs">
                <?php foreach ($tiposPermitidos as $tipoDolar): ?>
                    <div class="nav-tab <?php echo $tipo === $tipoDolar ? 'active' : ''; ?>" 
                        onclick="window.location.href='?tipo=<?php echo $tipoDolar; ?>'">
                        Dólar <?php echo formatearNombreDolar($tipoDolar); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <!-- Indicador de fuente de datos -->
            <div class="data-source <?php echo $mostrarDatosReales ? 'real' : 'backup'; ?>">
                <i class="fas fa-<?php echo $mostrarDatosReales ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php if ($mostrarDatosReales): ?>
                    <strong>✓ Datos en tiempo real</strong> - Conectado a dolarapi.com
                <?php else: ?>
                    <strong>⚠ Datos de respaldo</strong> - API temporalmente no disponible
                <?php endif; ?>
                <br>
                <small>Última actualización: <?php echo date('d/m/Y H:i:s', strtotime($ultimaActualizacion)); ?></small>
            </div>
            
            <!-- Cotización Principal -->
            <?php if ($cotizacion): ?>
            <div class="card featured-cotizacion">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-star"></i>
                        Dólar <?php echo formatearNombreDolar($tipo); ?>
                        <?php if ($variacion != 0): ?>
                            <span class="variacion <?php echo $variacion > 0 ? 'variacion-positiva' : 'variacion-negativa'; ?>">
                                <i class="fas fa-arrow-<?php echo $variacion > 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo number_format(abs($variacion), 2); ?>%
                            </span>
                        <?php endif; ?>
                    </h2>
                    <div class="card-badge">
                        <?php echo $cotizacion['casa'] ?? 'Fuente: BCRA'; ?>
                    </div>
                </div>
                
                <div class="featured-values">
                    <div class="featured-value">
                        <div class="label">COMPRA</div>
                        <div class="value">$<?php echo number_format($cotizacion['compra'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="featured-value">
                        <div class="label">VENTA</div>
                        <div class="value">$<?php echo number_format($cotizacion['venta'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="featured-value">
                        <div class="label">DIFERENCIA</div>
                        <div class="value">
                            $<?php echo number_format(($cotizacion['venta'] - $cotizacion['compra']), 2, ',', '.'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="updated-time" style="text-align: center; margin-top: 1rem;">
                    <i class="far fa-clock"></i>
                    Actualizado: <?php echo date('d/m/Y H:i:s', strtotime($cotizacion['fechaActualizacion'] ?? 'now')); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dashboard de Cotizaciones -->
            <div class="dashboard">
                <!-- Todas las cotizaciones de dólar -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign"></i>
                            Todos los Dólares
                        </h3>
                        <span class="api-badge"><?php echo count($todasCotizaciones); ?> tipos</span>
                    </div>
                    
                    <?php if (!empty($todasCotizaciones)): ?>
                        <?php foreach ($todasCotizaciones as $cot): ?>
                        <div class="cotizacion-item">
                            <div class="cotizacion-tipo">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <div><?php echo $cot['nombre'] ?? $cot['casa']; ?></div>
                                    <small style="font-size: 0.8rem; color: #7f8c8d;">
                                        <?php echo $cot['moneda'] ?? 'USD'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="cotizacion-valores">
                                <div class="valor-compra">
                                    $<?php echo number_format($cot['compra'], 2, ',', '.'); ?>
                                </div>
                                <div class="valor-venta">
                                    $<?php echo number_format($cot['venta'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-exclamation-circle"></i><br>
                            No hay datos disponibles
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Otras Monedas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-globe-americas"></i>
                            Otras Monedas
                        </h3>
                        <span class="api-badge"><?php echo count($otrasMonedas); ?> monedas</span>
                    </div>
                    
                    <?php if (!empty($otrasMonedas)): ?>
                        <?php foreach ($otrasMonedas as $moneda): ?>
                        <div class="cotizacion-item">
                            <div class="cotizacion-tipo">
                                <i class="fas fa-coins"></i>
                                <div>
                                    <div><?php echo $moneda['nombre']; ?></div>
                                    <small style="font-size: 0.8rem; color: #7f8c8d;">
                                        <?php echo $moneda['moneda'] ?? 'USD'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="cotizacion-valores">
                                <div class="valor-compra">
                                    $<?php echo number_format($moneda['compra'], 2, ',', '.'); ?>
                                </div>
                                <div class="valor-venta">
                                    $<?php echo number_format($moneda['venta'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-globe-americas"></i><br>
                            No hay otras monedas disponibles
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información del Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Información del Sistema
                        </h3>
                    </div>
                    <div style="padding: 1rem 0;">
                        <div style="margin-bottom: 15px;">
                            <strong>Estado del sistema:</strong><br>
                            <span style="color: <?php echo $mostrarDatosReales ? '#28a745' : '#ffc107'; ?>;">
                                <i class="fas fa-circle" style="font-size: 0.7rem;"></i>
                                <?php echo $mostrarDatosReales ? 'API funcionando' : 'API en respaldo'; ?>
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Base de Datos:</strong><br>
                            <span style="color: <?php echo $dbAvailable ? '#28a745' : '#dc3545'; ?>;">
                                <i class="fas fa-circle" style="font-size: 0.7rem;"></i>
                                <?php echo $dbAvailable ? 'Conectada' : 'No disponible'; ?>
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Registros hoy:</strong><br>
                            <span style="font-size: 1.2rem; font-weight: bold;">
                                <?php echo array_sum(array_column($estadisticasDia, 'total_registros')); ?>
                            </span>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong>Cache:</strong><br>
                            <span style="color: #28a745;">
                                <i class="fas fa-circle" style="font-size: 0.7rem;"></i>
                                Activo (5 minutos)
                            </span>
                        </div>
                        
                        <!-- <?php if ($dbAvailable): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <a href="?tipo=<?php echo $tipo; ?>&guardar=1" class="save-btn">
                                <i class="fas fa-save"></i> Guardar en Base de Datos
                            </a>
                            <p style="font-size: 0.8rem; color: #7f8c8d; margin-top: 5px;">
                                Guarda esta cotización en el histórico
                            </p>
                        </div> -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>
                <i class="fas fa-user"></i> Desarrollado por: Santiago Filipeli |     
                <i class="fas fa-code"></i> Desarrollado con PHP 8.3.14 | 
                <i class="fas fa-database"></i> MariaDB | 
                <i class="fas fa-api"></i> API: dolarapi.com
            </p>
            <p style="margin-top: 10px; font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> - Seguimiento del Dólar en Argentina |
                <a href="https://dolarapi.com/docs/argentina/" target="_blank" style="color: var(--secondary-color); text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> Documentación API
                </a>
            </p>    
            <p style="margin-top: 5px; font-size: 0.8rem; color: #95a5a6;">
                <i class="fas fa-exclamation-triangle"></i> Los valores son informativos. Consulte con su entidad financiera.
            </p>
        </div>
    </footer>
    
    <script>
        // Auto-refresh cada 5 minutos (300,000 milisegundos)
        setTimeout(function() {
            window.location.reload();
        }, 300000);
        
        // Mostrar hora actual actualizada
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-AR', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = 'Hora: ' + timeString;
        }
        
        // Actualizar cada segundo
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime(); // Ejecutar inmediatamente
        
        // Efecto de hover en tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Efecto en botones de pestañas
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
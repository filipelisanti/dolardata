<?php
// app/models/CotizacionModel.php

class CotizacionModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Guarda una cotización en la base de datos
     */
    public function guardarCotizacion($data) {
        $sql = "INSERT INTO cotizaciones 
                (tipo_dolar, nombre, compra, venta, fecha_actualizacion, casa, moneda) 
                VALUES 
                (:tipo_dolar, :nombre, :compra, :venta, :fecha_actualizacion, :casa, :moneda)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':tipo_dolar' => $data['tipo_dolar'] ?? $data['casa'],
            ':nombre' => $data['nombre'],
            ':compra' => $data['compra'],
            ':venta' => $data['venta'],
            ':fecha_actualizacion' => $data['fechaActualizacion'] ?? date('Y-m-d H:i:s'),
            ':casa' => $data['casa'],
            ':moneda' => $data['moneda'] ?? 'USD'
        ]);
    }
    
    /**
     * Obtiene la última cotización de un tipo específico
     */
    public function getUltimaCotizacion($tipo) {
        $sql = "SELECT * FROM cotizaciones 
                WHERE tipo_dolar = :tipo 
                ORDER BY fecha_actualizacion DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo' => $tipo]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtiene todas las cotizaciones recientes
     */
    public function getCotizacionesRecientes($limit = 10) {
        $sql = "SELECT c1.* 
                FROM cotizaciones c1
                INNER JOIN (
                    SELECT tipo_dolar, MAX(fecha_actualizacion) as max_fecha
                    FROM cotizaciones
                    GROUP BY tipo_dolar
                ) c2 ON c1.tipo_dolar = c2.tipo_dolar AND c1.fecha_actualizacion = c2.max_fecha
                ORDER BY c1.venta DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene histórico de un tipo de dólar
     */
    public function getHistorico($tipo, $dias = 7) {
        $sql = "SELECT 
                    DATE(fecha_actualizacion) as fecha,
                    AVG(compra) as compra_promedio,
                    AVG(venta) as venta_promedio,
                    MAX(compra) as compra_max,
                    MIN(compra) as compra_min,
                    MAX(venta) as venta_max,
                    MIN(venta) as venta_min,
                    COUNT(*) as registros
                FROM cotizaciones
                WHERE tipo_dolar = :tipo 
                AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)
                GROUP BY DATE(fecha_actualizacion)
                ORDER BY fecha DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo' => $tipo, ':dias' => $dias]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene estadísticas del día
     */
    public function getEstadisticasDia() {
        $sql = "SELECT 
                    tipo_dolar,
                    COUNT(*) as total_registros,
                    MAX(venta) as venta_maxima,
                    MIN(venta) as venta_minima,
                    AVG(venta) as venta_promedio,
                    MAX(compra) as compra_maxima,
                    MIN(compra) as compra_minima,
                    AVG(compra) as compra_promedio
                FROM cotizaciones
                WHERE DATE(fecha_actualizacion) = CURDATE()
                GROUP BY tipo_dolar
                ORDER BY venta_promedio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verifica si necesita actualizar (evita duplicados muy cercanos en tiempo)
     */
    public function necesitaActualizar($tipo, $minutos = 5) {
        $sql = "SELECT COUNT(*) as count 
                FROM cotizaciones 
                WHERE tipo_dolar = :tipo 
                AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo' => $tipo, ':minutos' => $minutos]);
        
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }
    
    /**
     * Limpia registros antiguos (mantiene solo 30 días)
     */
    public function limpiarRegistrosAntiguos($dias = 30) {
        $sql = "DELETE FROM cotizaciones 
                WHERE fecha_actualizacion < DATE_SUB(NOW(), INTERVAL :dias DAY)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':dias' => $dias]);
    }
}
?>
<?php
// app/config/database.php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $host = Config::get('DB_HOST', 'localhost');
            $dbname = Config::get('DB_NAME', 'dolar_argentina');
            $username = Config::get('DB_USER', 'root');
            $password = Config::get('DB_PASS', 'juansan2002');
            
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            // Para desarrollo, muestra el error
            if (Config::get('ENVIRONMENT', 'development') === 'development') {
                die("Error de conexión a BD: " . $e->getMessage());
            } else {
                die("Error de conexión a la base de datos.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
    
    // Métodos helper para consultas comunes
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->connection->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }
    
    public function select($table, $conditions = [], $orderBy = '', $limit = '') {
        $sql = "SELECT * FROM $table";
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "$key = :$key";
            }
            $sql .= implode(' AND ', $whereParts);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->connection->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
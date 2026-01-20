<?php
namespace App\Config;
use PDO;
use PDOException;

class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db_name = "sst_manager"; // Valor por defecto

    public function __construct($dbName = null) {
        if ($dbName) {
            $this->db_name = $dbName;
        }
    }

    public function getConnection() {
        try {
            $conn = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
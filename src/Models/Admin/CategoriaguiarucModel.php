<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class CategoriaguiarucModel extends GenericModel {
    
    public function __construct($db) {
        // Definimos la tabla 'categorias_guia_ruc'
        parent::__construct($db, 'categorias_guia_ruc');
    }

    /**
     * Crea la estructura de la tabla en la base de datos
     */
    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS categorias_guia_ruc (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            descripcion VARCHAR(255) NOT NULL,
            estado TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $this->db->exec($sql);
            return true;
        } catch (\PDOException $e) {
            error_log("Error al instalar tabla categorias_guia_ruc: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener solo las categorías activas para los selects
     */
    public function getActivas() {
        $sql = "SELECT id, codigo, descripcion FROM {$this->table} WHERE estado = 1 ORDER BY codigo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
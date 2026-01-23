<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class PerfilModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'perfiles');
    }

    public function install() {
        $sqlPerfiles = "CREATE TABLE IF NOT EXISTS perfiles (
            id_perfil INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre_perfil VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            id_empresa INT(11) NULL, -- NULL = Perfil Global
            estado TINYINT(1) DEFAULT 1,
            UNIQUE KEY unique_perfil_empresa (nombre_perfil, id_empresa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sqlPerfiles);
        $this->seedMasterProfile();
    }

    private function seedMasterProfile() {
        $stmt = $this->db->prepare("SELECT id_perfil FROM perfiles WHERE nombre_perfil = 'Master' AND id_empresa IS NULL");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $sql = "INSERT INTO perfiles (nombre_perfil, descripcion, id_empresa) 
                    VALUES ('Master', 'Perfil Maestro: Control total del ecosistema SaaS', NULL)";
            $this->db->exec($sql);
            return $this->db->lastInsertId();
        }
        return false;
    }
}
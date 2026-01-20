<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
class PerfilModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'perfiles');
    }

    public function install() {
        // 1. Tabla de Perfiles con discriminador de empresa
        $sqlPerfiles = "CREATE TABLE IF NOT EXISTS perfiles (
            id_perfil INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre_perfil VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            
            -- LLAVE MULTI-EMPRESA (NULL = Perfil Global del Sistema)
            id_empresa INT(11) NULL, 
            
            estado TINYINT(1) DEFAULT 1,
            
            -- Un mismo nombre de perfil no debe repetirse dentro de la misma empresa
            UNIQUE KEY unique_perfil_empresa (nombre_perfil, id_empresa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sqlPerfiles);

        // 2. Tabla de Permisos (Se mantiene igual, ya está amarrada al id_perfil)
        $sqlPermisos = "CREATE TABLE IF NOT EXISTS perfil_permisos (
            id_permiso INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_perfil INT(11) NOT NULL,
            id_modulo INT(11) NOT NULL,
            can_ver TINYINT(1) DEFAULT 0,
            can_crear TINYINT(1) DEFAULT 0,
            can_editar TINYINT(1) DEFAULT 0,
            can_eliminar TINYINT(1) DEFAULT 0,
            UNIQUE KEY unique_permiso (id_perfil, id_modulo),
            CONSTRAINT fk_permisos_perfil FOREIGN KEY (id_perfil) REFERENCES perfiles(id_perfil) ON DELETE CASCADE,
            CONSTRAINT fk_permisos_modulo FOREIGN KEY (id_modulo) REFERENCES modulos(id_modulo) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sqlPermisos);

        $this->seedAdminProfile();
    }

    private function seedAdminProfile() {
    // 1. Verificamos si existe el Perfil MASTER (Global)
    $stmt = $this->db->prepare("SELECT id_perfil FROM perfiles WHERE nombre_perfil = 'Master' AND id_empresa IS NULL");
    $stmt->execute();
    $idMaster = $stmt->fetchColumn();

    if (!$idMaster) {
        $sql = "INSERT INTO perfiles (nombre_perfil, descripcion, id_empresa) 
                VALUES ('Master', 'Perfil Maestro: Control total del ecosistema SaaS', NULL)";
        $this->db->exec($sql);
        $idMaster = $this->db->lastInsertId();
        echo "      (👑 Perfil MASTER creado exitosamente)\n";

        // Asignamos permisos totales a todos los módulos existentes para el Master
        $modulos = $this->db->query("SELECT id_modulo FROM modulos")->fetchAll(\PDO::FETCH_ASSOC);
        $stmtPerm = $this->db->prepare("INSERT IGNORE INTO perfil_permisos (id_perfil, id_modulo, can_ver, can_crear, can_editar, can_eliminar) VALUES (?, ?, 1, 1, 1, 1)");
        
        foreach ($modulos as $mod) {
            $stmtPerm->execute([$idMaster, $mod['id_modulo']]);
        }
    }
    }
}
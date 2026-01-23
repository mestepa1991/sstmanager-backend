<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class PermisoModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'perfil_permisos');
    }

    public function install() {
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
    }

    /**
     * Asigna permisos totales a un perfil para todos los módulos
     */
    public function assignFullAccess($id_perfil) {
        $modulos = $this->db->query("SELECT id_modulo FROM modulos")->fetchAll(\PDO::FETCH_ASSOC);
        $stmt = $this->db->prepare("INSERT IGNORE INTO perfil_permisos 
            (id_perfil, id_modulo, can_ver, can_crear, can_editar, can_eliminar) 
            VALUES (?, ?, 1, 1, 1, 1)");
        
        foreach ($modulos as $mod) {
            $stmt->execute([$id_perfil, $mod['id_modulo']]);
        }
    }
    
    /**
     * Obtiene la matriz de permisos de un perfil específico
     */
    public function getByPerfil($id_perfil) {
        $stmt = $this->db->prepare("SELECT * FROM perfil_permisos WHERE id_perfil = ?");
        $stmt->execute([$id_perfil]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
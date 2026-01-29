<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
use PDO;

class PermisoModel extends GenericModel {
    
    public function __construct($db) {
        // 'perfil_permisos' es la tabla pivote
        parent::__construct($db, 'perfil_permisos');
    }

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS perfil_permisos (
            id_permiso INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_perfil INT(11) NOT NULL,
            id_modulo INT(11) NOT NULL,
            can_ver TINYINT(1) DEFAULT 0,
            can_crear TINYINT(1) DEFAULT 0,
            can_editar TINYINT(1) DEFAULT 0,
            can_eliminar TINYINT(1) DEFAULT 0,
            UNIQUE KEY unique_permiso (id_perfil, id_modulo),
            CONSTRAINT fk_pp_perfil FOREIGN KEY (id_perfil) REFERENCES perfiles(id_perfil) ON DELETE CASCADE,
            CONSTRAINT fk_pp_modulo FOREIGN KEY (id_modulo) REFERENCES modulos(id_modulo) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }

    /**
     * Obtiene la matriz completa combinando módulos y permisos asignados.
     * Usa LEFT JOIN para asegurar que listamos todos los módulos, tengan permiso o no (opcional).
     * O INNER JOIN si solo quieres ver lo que ya está configurado.
     */
    public function getMatrix($id_perfil) {
       // Columnas según tu esquema
    $sql = "SELECT id_perfil, id_modulo, can_ver, can_crear, can_editar, can_eliminar 
            FROM perfil_permisos 
            WHERE id_perfil = ?";
    
    // Usamos prepare para evitar el TypeError de PDO::query
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id_perfil]);
    
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Borra todos los permisos de un perfil (preparación para guardar nuevos).
     */
    public function clearPermisos($id_perfil) {
        $stmt = $this->db->prepare("DELETE FROM perfil_permisos WHERE id_perfil = ?");
        return $stmt->execute([$id_perfil]);
    }
}
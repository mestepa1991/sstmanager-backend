<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
class ModuloModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'modulos');
    }

    public function install() {
    // Forzamos el DROP para asegurar que la nueva estructura se aplique
    
    $sql = "CREATE TABLE IF NOT EXISTS modulos (
    `id_modulo` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `id_padre` INT(11) NULL DEFAULT NULL, -- NULL = Módulo, Numero = Función
    `nombre_modulo` VARCHAR(100) NOT NULL, -- Quité el UNIQUE global para evitar conflictos si dos módulos tienen una función con el mismo nombre
    `descripcion` TEXT NULL,
    `icono` VARCHAR(50) DEFAULT 'fas fa-cube',
    `estado` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`id_padre`) REFERENCES `modulos`(`id_modulo`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $this->db->exec($sql);

    }

    // Lista maestra de módulos del sistema
    private function seedModulosIniciales() {
        $modulosBase = [
            ['nombre' => 'ADMINISTRCION', 'desc' => 'Panel de control principal'],
            ['nombre' => 'SEGURIDAD', 'desc' => 'Gestión de usuarios y accesos'],
            ['nombre' => 'EMPRESAS', 'desc' => 'Configuración de roles y permisos']            
        ];

        // Cambia ':desc' por ':descripcion' para que coincida con el nombre de la columna física
        $stmt = $this->db->prepare("INSERT IGNORE INTO modulos (nombre_modulo, descripcion) VALUES (:nombre, :descripcion)");

        foreach ($modulosBase as $mod) {
            $stmt->execute([
                ':nombre'      => $mod['nombre'], 
                ':descripcion' => $mod['desc'] // Usamos la clave 'desc' del array para llenar la columna 'descripcion'
            ]);
        }
        
        // Solo para debug visual en consola
        echo "      (🌱 Módulos base verificados/insertados)\n";
    }
}
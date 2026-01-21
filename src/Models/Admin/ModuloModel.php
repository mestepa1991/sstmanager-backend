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
        `nombre_modulo` VARCHAR(100) NOT NULL UNIQUE,
        `descripcion` TEXT NULL,
        `icono` VARCHAR(50) DEFAULT 'fas fa-cube',
        `estado` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $this->db->exec($sql);
    $this->seedModulosIniciales();
    }

    // Lista maestra de módulos del sistema
    private function seedModulosIniciales() {
        $modulosBase = [
            ['nombre' => 'Dashboard', 'desc' => 'Panel de control principal'],
            ['nombre' => 'Usuarios', 'desc' => 'Gestión de usuarios y accesos'],
            ['nombre' => 'Perfiles', 'desc' => 'Configuración de roles y permisos'],
            ['nombre' => 'Planes', 'desc' => 'Gestión de planes de suscripción'],
            ['nombre' => 'Empresas', 'desc' => 'Administración de clientes/empresas'],
            ['nombre' => 'Normatividad', 'desc' => 'Matriz legal y normas'],
            ['nombre' => 'Formularios', 'desc' => 'Creador de formularios dinámicos']
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
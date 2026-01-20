<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
class PlanModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'planes');
    }

    public function install() {
        // 1. Tabla Maestra de Planes
        $sqlPlanes = "CREATE TABLE IF NOT EXISTS planes (
            id_plan INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre_plan VARCHAR(100) NOT NULL UNIQUE,
            descripcion TEXT NULL,
            limite_usuarios INT(11) DEFAULT 0, -- 0 = Ilimitado
            precio_mensual DECIMAL(10,2) DEFAULT 0.00,
            estado TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sqlPlanes);

        // 2. Tabla Intermedia: Relación Plan <-> Módulos
        // Aquí definimos qué "paquete" tiene qué "herramientas"
        $sqlPlanModulos = "CREATE TABLE IF NOT EXISTS plan_modulos (
            id_plan INT(11) NOT NULL,
            id_modulo INT(11) NOT NULL,
            PRIMARY KEY (id_plan, id_modulo),
            CONSTRAINT fk_pm_plan FOREIGN KEY (id_plan) 
                REFERENCES planes(id_plan) ON DELETE CASCADE,
            CONSTRAINT fk_pm_modulo FOREIGN KEY (id_modulo) 
                REFERENCES modulos(id_modulo) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sqlPlanModulos);

        // 3. Insertar datos iniciales
        $this->seedPlanes();
    }

    private function seedPlanes() {
        $check = $this->db->query("SELECT COUNT(*) FROM planes")->fetchColumn();
        if ($check == 0) {
            // Creamos 3 niveles comerciales típicos
            $this->db->exec("INSERT INTO planes (nombre_plan, descripcion, limite_usuarios, precio_mensual) VALUES 
                ('Básico', 'Ideal para microempresas', 5, 0.00),
                ('Profesional', 'Para empresas en crecimiento', 20, 49.90),
                ('Enterprise', 'Sin límites de gestión', 0, 120.00)");
            
            echo "      (📦 Planes comerciales iniciales creados)\n";
        }
    }
}
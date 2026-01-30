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

        // 2. Insertar datos iniciales (Seed)
        $this->seedPlanes();
    }

    /**
     * Inserta los planes iniciales siguiendo la lógica de validación de PerfilModel
     */
    private function seedPlanes() {
        // Definimos los planes semilla
        $planesSemilla = [
            ['Básico', 'Ideal para microempresas', 5, 0.00],
            ['Profesional', 'Para empresas en crecimiento', 20, 49.90],
            ['Enterprise', 'Sin límites de gestión', 0, 120.00]
        ];

        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM planes WHERE nombre_plan = ?");
        $stmtInsert = $this->db->prepare("INSERT INTO planes (nombre_plan, descripcion, limite_usuarios, precio_mensual) VALUES (?, ?, ?, ?)");

        foreach ($planesSemilla as $plan) {
            $stmtCheck->execute([$plan[0]]);
            if ($stmtCheck->fetchColumn() == 0) {
                $stmtInsert->execute($plan);
            }
        }
    }
}
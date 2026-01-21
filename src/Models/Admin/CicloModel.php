<?php
namespace App\Models\Admin; // <--- Namespace ajustado a la carpeta Admin

use App\Models\GenericModel; // Importamos el padre

class CicloModel extends GenericModel {
    
    public function __construct($db) {
        // Nombre exacto de la tabla en base de datos
        parent::__construct($db, 'ciclos_phva'); 
    }

    public function install() {
        // 1. Crear tabla
        $sql = "CREATE TABLE IF NOT EXISTS ciclos_phva (
            id_ciclo INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $this->db->exec($sql);

        // 2. Insertar datos constantes (Seed)
        $this->seedCiclos();
    }

    private function seedCiclos() {
        // Solo insertamos si la tabla está vacía
        $stmt = $this->db->query("SELECT COUNT(*) FROM ciclos_phva");
        if ($stmt->fetchColumn() == 0) {
            $ciclos = ['Planear', 'Hacer', 'Verificar', 'Actuar'];
            
            $sql = "INSERT INTO ciclos_phva (nombre) VALUES (?)";
            $stmt = $this->db->prepare($sql);

            foreach ($ciclos as $ciclo) {
                $stmt->execute([$ciclo]);
            }
            echo "      🔄 Tabla 'ciclos_phva' creada y poblada.\n";
        }
    }
}
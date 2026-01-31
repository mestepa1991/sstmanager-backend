<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class TipoempresaModel extends GenericModel {
    
    public function __construct($db) {
        // Sugerencia: Usa minúsculas para nombres de tablas si estás en Linux para evitar problemas de case-sensitivity
        parent::__construct($db, 'tipo_empresa');
    }

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS tipo_empresa (
            `id_config` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `tamano_empresa` ENUM('Micro', 'Pequeña', 'Mediana', 'Grande') NOT NULL DEFAULT 'Micro',
            `empleados_desde` INT(11) NOT NULL,
            `empleados_hasta` INT(11) NOT NULL,
            `estado` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        
        $this->seedConfiguracionesIniciales();
    }

    private function seedConfiguracionesIniciales() {
        $check = $this->db->query("SELECT COUNT(*) FROM tipo_empresa")->fetchColumn();
        if ($check > 0) return;

        $seeds = [
            ['tamano' => 'Micro',   'desde' => 1,  'hasta' => 10],
            ['tamano' => 'Pequeña', 'desde' => 11, 'hasta' => 50],
            ['tamano' => 'Mediana', 'desde' => 51, 'hasta' => 200],
            ['tamano' => 'Grande',  'desde' => 201, 'hasta' => 9999]
        ];

        // Consulta limpia sin la columna 'sector'
        $sql = "INSERT INTO tipo_empresa 
                (tamano_empresa, empleados_desde, empleados_hasta, estado) 
                VALUES (:tamano, :desde, :hasta, 1)";
        
        $stmt = $this->db->prepare($sql);

        foreach ($seeds as $data) {
            // Corregido: eliminada la clave ':sector' que causaba error de PDO
            $stmt->execute([
                ':tamano' => $data['tamano'],
                ':desde'  => $data['desde'],
                ':hasta'  => $data['hasta']
            ]);
        }
        
        echo "      (🌱 Tipos de empresa base insertados)\n";
    }
}
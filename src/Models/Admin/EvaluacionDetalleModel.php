<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class EvaluacionDetalleModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'evaluacion_detalles'); 
    }

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS evaluacion_detalles (
            id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_evaluacion INT(11) NOT NULL,
            id_item_maestro INT(11) NOT NULL,
            cumple TINYINT(1) DEFAULT 0,
            observaciones TEXT NULL,
            CONSTRAINT fk_detalle_eval FOREIGN KEY (id_evaluacion) 
                REFERENCES evaluaciones(id_evaluacion) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $this->db->exec($sql);
        echo "   🔍 Tabla 'evaluacion_detalles' creada correctamente.\n";
    }
}
<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class EvaluacionModel extends GenericModel {
    
    public function __construct($db) {
        // Nombre de la tabla principal
        parent::__construct($db, 'evaluaciones'); 
    }

    public function install() {
    $sql = "CREATE TABLE IF NOT EXISTS evaluaciones (
        id_evaluacion INT(11) AUTO_INCREMENT PRIMARY KEY,
        id_empresa INT(11) NOT NULL,
        tipo_estandar VARCHAR(50),
        nombre_categoria VARCHAR(100),
        cantidad_empleados INT(11), -- <--- Añadir esto
        clase_riesgo INT(5),        -- <--- Añadir esto
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $this->db->exec($sql);
    }
}
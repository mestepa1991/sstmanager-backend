<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
use PDO;

class PlanmodulosModel extends GenericModel {
    
    public function __construct($db) {
        // 'plan_modulos' es la tabla pivote entre planes y módulos
        parent::__construct($db, 'plan_modulos');
    }

    public function install() {
        $sqlPlanModulos = "CREATE TABLE IF NOT EXISTS plan_modulos (
    id INT(11) NOT NULL AUTO_INCREMENT,   -- 1. Nueva llave primaria propia
    id_plan INT(11) NOT NULL,
    id_modulo INT(11) NOT NULL,
    ver TINYINT(1) DEFAULT 0,           
    
    PRIMARY KEY (id),                     -- 2. La definimos como la PK de la tabla
    
    -- Opcional: Es buena práctica mantener esto para no repetir asignaciones
    UNIQUE KEY unique_plan_modulo (id_plan, id_modulo), 

    CONSTRAINT fk_pm_plan FOREIGN KEY (id_plan) 
        REFERENCES planes(id_plan) ON DELETE CASCADE,
    CONSTRAINT fk_pm_modulo FOREIGN KEY (id_modulo) 
        REFERENCES modulos(id_modulo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sqlPlanModulos);
    }

    /**
     * Obtiene la matriz completa de módulos y permisos asignados a un plan.
     */
    public function getMatrix($id_plan) {
    // Usamos un JOIN para traer el nombre del módulo
    // Asumo que tu tabla de módulos se llama 'modulos' y tiene 'id_modulo' y 'modulo' (o 'nombre')
    $sql = "SELECT 
                pm.id_plan,
                pm.id_modulo,
                m.nombre_modulo as nombre_modulo,
                pm.ver
            FROM plan_modulos pm
            INNER JOIN modulos m ON pm.id_modulo = m.id_modulo
            WHERE pm.id_plan = :id_plan";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id_plan', $id_plan);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Borra todos los módulos/permisos de un plan (preparación para sincronizar).
     */
    public function clearModulos($id_plan) {
        $stmt = $this->db->prepare("DELETE FROM plan_modulos WHERE id_plan = ?");
        return $stmt->execute([$id_plan]);
    }
}
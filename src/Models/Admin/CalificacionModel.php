<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class CalificacionModel extends GenericModel {
    
    public function __construct($db) {
        // Por defecto, este modelo gestiona la tabla PADRE
        parent::__construct($db, 'calificaciones');
    }

    /**
     * Instala TODA la estructura (Padre e Hija)
     */
    public function install() {
        // ---------------------------------------------------------
        // 1. TABLA PADRE: 'calificaciones'
        // ---------------------------------------------------------
        $sqlPadre = "CREATE TABLE IF NOT EXISTS calificaciones (
            id_calificacion INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            estado VARCHAR(20) DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $this->db->exec($sqlPadre);

        // ---------------------------------------------------------
        // 2. TABLA HIJA: 'calificaciones_detalles'
        // ---------------------------------------------------------
        $sqlHija = "CREATE TABLE IF NOT EXISTS calificaciones_detalles (
            id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_calificacion INT(11) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            estado VARCHAR(20) DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $this->db->exec($sqlHija);

        // ---------------------------------------------------------
        // 3. RELACIÓN (Foreign Key)
        // ---------------------------------------------------------
        $this->checkAndAddForeignKey(
            'fk_detalle_calificacion', // Nombre de la llave
            'id_calificacion',         // Columna en la tabla hija
            'calificaciones',          // Tabla padre
            'id_calificacion'          // Columna en la tabla padre
        );
    }

    /**
     * Función auxiliar para crear la relación entre las tablas
     */
    private function checkAndAddForeignKey($fkName, $column, $refTable, $refColumn) {
        // Verificamos en la tabla HIJA (calificaciones_detalles)
        $sqlCheck = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                     WHERE CONSTRAINT_NAME = '$fkName' 
                     AND TABLE_SCHEMA = DATABASE()";
        
        if (!$this->db->query($sqlCheck)->fetchColumn()) {
            try {
                // Aplicamos el ALTER TABLE a la tabla hija
                $sql = "ALTER TABLE calificaciones_detalles 
                        ADD CONSTRAINT $fkName 
                        FOREIGN KEY ($column) REFERENCES $refTable($refColumn) 
                        ON DELETE CASCADE ON UPDATE CASCADE"; 
                $this->db->exec($sql);
                echo "      🔗 Relación Maestro-Detalle '$fkName' creada.\n";
            } catch (\Exception $e) {
                echo "      ⚠️ Error FK: " . $e->getMessage() . "\n";
            }
        }
    }
}
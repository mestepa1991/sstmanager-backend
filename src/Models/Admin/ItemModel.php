<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class ItemModel extends GenericModel {
    
    public function __construct($db) {
        // Por defecto, este modelo gestiona la tabla PADRE
        parent::__construct($db, 'estandares');
    }

    /**
     * Instala TODA la estructura (Padre e Hija)
     * Basado en los campos de la imagen proporcionada
     */
    public function install() {
        $sqlHija = "CREATE TABLE IF NOT EXISTS item (
            id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_categorias INT(11) NOT NULL,
            item_estandar TEXT NOT NULL,
            item TEXT NOT NULL,
            criterio TEXT NOT NULL,
            modo_verificacion TEXT NOT NULL,
            estado VARCHAR(20) DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $this->db->exec($sqlHija);

        // ---------------------------------------------------------
        // 2. RELACIÓN (Foreign Key)
        // ---------------------------------------------------------
        $this->checkAndAddForeignKey(
            'fk_detalle_estandar', // Nombre de la llave
            'id_categorias',         // Columna en la tabla hija
            'categoria_tipos',          // Tabla padre
            'id'          // Columna en la tabla padre
        );
    }

    /**
     * Función auxiliar para crear la relación entre las tablas
     */
    private function checkAndAddForeignKey($fkName, $column, $refTable, $refColumn) {
        // Verificamos en la tabla HIJA (item)
        $sqlCheck = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                     WHERE CONSTRAINT_NAME = '$fkName' 
                     AND TABLE_SCHEMA = DATABASE()";
        
        if (!$this->db->query($sqlCheck)->fetchColumn()) {
            try {
                // Aplicamos el ALTER TABLE a la tabla hija
                $sql = "ALTER TABLE item 
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
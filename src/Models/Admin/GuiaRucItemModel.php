<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="GuiaRucItemModel",
 * title="Modelo de Ítems Guía RUC",
 * description="Estructura de la tabla de ítems de la Guía RUC con relación a categorías"
 * )
 */
class GuiaRucItemModel extends GenericModel {

    public function __construct($db) {
        // Definimos la tabla 'guia_ruc_items'
        parent::__construct($db, 'guia_ruc_items');
    }

    /**
     * Crea la estructura de la tabla en la base de datos
     */
    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS guia_ruc_items (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_categoria INT(11) NOT NULL,
            num_item VARCHAR(50) NOT NULL,
            requisito TEXT NOT NULL,
            descripcion TEXT,
            observaciones TEXT,
            estado TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Llave foránea hacia la tabla de categorías creada anteriormente
            CONSTRAINT fk_guia_ruc_categoria 
            FOREIGN KEY (id_categoria) 
            REFERENCES categorias_guia_ruc(id) 
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $this->db->exec($sql);
            return true;
        } catch (\PDOException $e) {
            error_log("Error al instalar tabla guia_ruc_items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ítems con el nombre de su categoría (JOIN)
     */
    public function getItemsConCategoria() {
        $sql = "SELECT i.*, c.codigo as cat_codigo, c.descripcion as cat_nombre 
                FROM {$this->table} i
                INNER JOIN categorias_guia_ruc c ON i.id_categoria = c.id
                WHERE i.estado = 1
                ORDER BY c.codigo ASC, i.num_item ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
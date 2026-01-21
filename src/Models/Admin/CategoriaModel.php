<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class CategoriaModel extends GenericModel {
    public function __construct($db) {
        parent::__construct($db, 'categorias');
    }

    public function install() {
        // TABLA PADRE: CATEGORIAS
        $this->db->exec("CREATE TABLE IF NOT EXISTS categorias (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            descripcion VARCHAR(255) NOT NULL,
            estado TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // TABLA HIJA: CATEGORIA_TIPOS
        $this->db->exec("CREATE TABLE IF NOT EXISTS categoria_tipos (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            categoria_id INT(11) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            estado TINYINT(1) DEFAULT 1,
            CONSTRAINT fk_tipo_categoria FOREIGN KEY (categoria_id) 
                REFERENCES categorias(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}
<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class EmpresaModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'empresas');
    }

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS empresas (
            id_empresa INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre_empresa VARCHAR(150) NOT NULL,
            nit VARCHAR(20) NOT NULL UNIQUE,
            id_plan INT(11) NOT NULL,
            
            -- Datos de contacto
            email_contacto VARCHAR(100) NULL,
            telefono VARCHAR(20) NULL,
            direccion TEXT NULL,
            logo_url TEXT NULL,
            
            -- Control de negocio
            estado TINYINT(1) DEFAULT 1, -- 1: Activa, 0: Suspendida
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            -- Integridad
            CONSTRAINT fk_empresa_plan FOREIGN KEY (id_plan) 
                REFERENCES planes(id_plan) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
    }
}
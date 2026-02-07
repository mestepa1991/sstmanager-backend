<?php
namespace App\Models\Admin;

use App\Models\GenericModel;

class PersonalSstModel extends GenericModel {
    
    public function __construct($db) {
        // Definimos la tabla 'personal_sst'
        parent::__construct($db, 'personal_sst');
    }

    public function install() {
        // Estructura basada en los campos de la imagen: Proyecto, Profesional, Correo, Teléfono y Firma
        $sql = "CREATE TABLE IF NOT EXISTS personal_sst (
            id_personal_sst INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_empresa INT(11) NOT NULL, -- Relación con la tabla empresas
            proyecto_rige VARCHAR(255) NOT NULL, -- Campo 'PROYECTO RIGE'
            nombre_profesional VARCHAR(150) NOT NULL, -- Campo 'PROFESIONAL SST'
            correo_sst VARCHAR(100) NOT NULL, -- Campo 'CORREO SST'
            telefono_sst VARCHAR(20) NULL, -- Campo 'TELÉFONO SST'
            firma_sst_url TEXT NULL, -- Campo 'FIRMA SST' (Ruta del archivo o string)
            estado TINYINT(1) DEFAULT 1,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            -- Integridad Referencial
            CONSTRAINT fk_personal_empresa FOREIGN KEY (id_empresa) 
                REFERENCES empresas(id_empresa) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
    }
}
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
            tipo_documento VARCHAR(10) DEFAULT 'NIT',
            numero_documento VARCHAR(50) NOT NULL UNIQUE,
            email_contacto VARCHAR(100) NULL,
            telefono VARCHAR(20) NULL,
            direccion TEXT NULL,
            logo_url TEXT NULL,
            estado TINYINT(1) DEFAULT 1,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
        $this->actualizarEstructura();
    }

    private function actualizarEstructura() {
        $nuevosCampos = [
            "id_plan"           => "INT(11) NOT NULL AFTER numero_documento",
            "nombre_rl"         => "VARCHAR(150) NULL AFTER direccion",
            "documento_rl"      => "VARCHAR(20) NULL AFTER nombre_rl",
            "cant_directos"     => "INT DEFAULT 0",
            "cant_contratistas" => "INT DEFAULT 0",
            "cant_aprendices"   => "INT DEFAULT 0",
            "cant_brigadistas"  => "INT DEFAULT 0"
        ];

        foreach ($nuevosCampos as $columna => $definicion) {
            $check = $this->db->query("SHOW COLUMNS FROM empresas LIKE '$columna'")->fetch();
            if (!$check) {
                $this->db->exec("ALTER TABLE empresas ADD COLUMN $columna $definicion");
            }
        }

        $this->crearLlaveForanea();
    }

    private function crearLlaveForanea() {
        $sqlCheck = "SELECT CONSTRAINT_NAME
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_NAME = 'empresas'
                     AND CONSTRAINT_NAME = 'fk_empresa_plan'
                     AND TABLE_SCHEMA = DATABASE()";

        $exists = $this->db->query($sqlCheck)->fetch();

        if (!$exists) {
            try {
                $this->db->exec("ALTER TABLE empresas
                                 ADD CONSTRAINT fk_empresa_plan
                                 FOREIGN KEY (id_plan) REFERENCES planes(id_plan)
                                 ON DELETE RESTRICT ON UPDATE CASCADE");
            } catch (\PDOException $e) {
                error_log("No se pudo crear la FK: " . $e->getMessage());
            }
        }
    }
}

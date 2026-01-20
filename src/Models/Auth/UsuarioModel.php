<?php
namespace App\Models\Auth;

use App\Models\GenericModel;
class UsuarioModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'usuarios');
    }

    public function install() {
       $sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    -- ACTUALIZACIÓN DEL ENUM
    rol ENUM('Master', 'Administrador', 'Usuario', 'Soporte') NOT NULL DEFAULT 'Usuario',
    
    id_empresa INT(11) NULL, 
    id_perfil INT(11) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_user_perfil FOREIGN KEY (id_perfil) REFERENCES perfiles(id_perfil)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
    }
}
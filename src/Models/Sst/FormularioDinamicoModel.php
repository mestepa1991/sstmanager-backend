<?php
namespace App\Models\Sst;

use App\Models\GenericModel;
use PDO;

class FormularioDinamicoModel extends GenericModel {
    public function __construct($db) {
        parent::__construct($db, 'sst_formularios_personalizados');
    }

    public function install() {
        $this->createTable([
            'id_empresa'         => 'INT(11) NOT NULL',
            'id_item_sst'        => 'INT(11) NOT NULL',
            'datos_json'         => 'JSON NOT NULL',
            'usuario_editor'     => 'INT(11)',
            'fecha_actualizacion' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        
        // Añadimos el índice único manual para el UPSERT
        $this->db->exec("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `uk_empresa_item` (`id_empresa`, `id_item_sst`) ");
    }

    public function getByEmpresaItem(int $id_empresa, int $id_item) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `id_empresa` = ? AND `id_item_sst` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_empresa, $id_item]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveFormContent(int $id_empresa, int $id_item, array $datos, int $id_usuario) {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO `{$this->table}` (`id_empresa`, `id_item_sst`, `datos_json`, `usuario_editor`) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                `datos_json` = VALUES(`datos_json`), 
                `usuario_editor` = VALUES(`usuario_editor`)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_empresa, $id_item, $json, $id_usuario]);
    }
}
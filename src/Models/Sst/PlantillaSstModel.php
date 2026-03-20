<?php
namespace App\Models\Sst;

use App\Models\GenericModel;

class PlantillaSstModel extends GenericModel {
    public function __construct($db) {
        parent::__construct($db, 'sst_formularios_plantillas');
    }

    // Usamos tu método createTable del GenericModel
    public function install() {
        $this->createTable([
            'id_item_sst'        => 'INT(11) NOT NULL UNIQUE',
            'nombre_referencia'  => 'VARCHAR(100)',
            'datos_json_base'    => 'JSON NOT NULL',
            'ultima_actualizacion' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
    }

    public function getByItem(int $id_item) {
        $sql = "SELECT datos_json_base FROM `{$this->table}` WHERE `id_item_sst` = :id_item";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_item' => $id_item]);
        return $stmt->fetchColumn();
    }
}
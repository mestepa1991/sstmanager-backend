<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="PlanAcceso", title="Matriz de Accesos por Plan")
 */
class PlanmodulosSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_plan;

    /** @OA\Property(type="integer", example=10) */
    public $id_modulo;

    /** @OA\Property(type="integer", example=1, description="1=Si, 0=No") */
    public $ver;
    
    public static function toList($data) {
        return array_map(function($item) {
            // Normalizamos las llaves a minúsculas
            $item = array_change_key_case($item, CASE_LOWER);
            
            return [
                'id_plan'   => (int)($item['id_plan'] ?? 0),
                'id_modulo' => (int)($item['id_modulo'] ?? 0),
                'ver'       => (int)($item['ver'] ?? 0)
            ];
        }, $data);
    }
}
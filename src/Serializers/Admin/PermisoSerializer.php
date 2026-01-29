<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Permiso", title="Matriz de Permisos")
 */
class PermisoSerializer {

    /** @OA\Property(type="integer", example=10) */
    public $id_modulo;

    /** @OA\Property(type="string", example="Ventas") */
    public $modulo;

    /** @OA\Property(type="integer", example=1, description="1=Si, 0=No") */
    public $ver;

    /** @OA\Property(type="integer", example=1) */
    public $crear;

    /** @OA\Property(type="integer", example=0) */
    public $editar;

    /** @OA\Property(type="integer", example=0) */
    public $eliminar;

    public static function toList($data) {
    return array_map(function($item) {
        // Convertimos todas las llaves a minúsculas para evitar errores de tipeo
        $item = array_change_key_case($item, CASE_LOWER);
        
        return [
            'id_perfil' => (int)($item['id_perfil'] ?? 0),
            'id_modulo' => (int)($item['id_modulo'] ?? 0),
            'ver'       => (int)($item['can_ver'] ?? 0),
            'crear'     => (int)($item['can_crear'] ?? 0),
            'editar'    => (int)($item['can_editar'] ?? 0),
            'eliminar'  => (int)($item['can_eliminar'] ?? 0)
        ];
    }, $data);
}
   
}
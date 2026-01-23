<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Perfil", title="Perfil de Usuario")
 */
class PerfilSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_perfil;

    /** @OA\Property(type="string", example="Administrador") */
    public $nombre_perfil;

    public static function toArray($data) {
        if (!$data) return null;
        
        return [
            'id_perfil'   => (int)$data['id_perfil'],
            'nombre_perfil'      => $data['nombre_perfil'],
            'descripcion' => $data['descripcion'] ?? '',
            'id_empresa'  => $data['id_empresa'] ? (int)$data['id_empresa'] : null,
            'tipo'        => $data['id_empresa'] ? 'Empresa' : 'Sistema (Master)',
            'estado'      => (int)($data['estado'] ?? 1)
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
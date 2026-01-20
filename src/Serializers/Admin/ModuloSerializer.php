<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Modulo",
 * title="Módulo del Sistema",
 * description="Esquema para la gestión de módulos y permisos"
 * )
 */
class ModuloSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_modulo;

    /** @OA\Property(type="string", example="Usuarios") */
    public $nombre_modulo;

    /** @OA\Property(type="string", example="Gestión de acceso") */
    public $descripcion;
    
    /** @OA\Property(type="integer", example=1, description="1: Activo, 0: Inactivo") */
    public $estado;
    
    public static function toArray($data) {
        if (!$data) return null;
        return [
            'id' => (int)$data['id_modulo'],
            'nombre' => $data['nombre_modulo'],
            'descripcion' => $data['descripcion'],
            'activo' => (bool)$data['estado'] // Importante para el frontend
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Perfil", title="Perfil de Usuario")
 */
class PerfilSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_perfil;

    /** @OA\Property(type="string", example="Administrador de Empresa") */
    public $nombre_perfil;

    /** @OA\Property(type="integer", nullable=true, description="NULL para globales, ID para locales") */
    public $id_empresa;

    public static function toArray($data, $permisos = []) {
        if (!$data) return null;
        
        return [
            'id' => (int)$data['id_perfil'],
            'nombre' => $data['nombre_perfil'],
            'descripcion' => $data['descripcion'] ?? '',
            'id_empresa' => $data['id_empresa'] ? (int)$data['id_empresa'] : null,
            'tipo' => $data['id_empresa'] ? 'Empresa' : 'Sistema (Master)',
            'activo' => (bool)($data['estado'] ?? 1),
            'permisos' => array_map(function($p) {
                return [
                    'id_modulo' => (int)$p['id_modulo'],
                    'modulo' => $p['nombre_modulo'] ?? 'Desconocido',
                    'ver' => (bool)$p['can_ver'],
                    'crear' => (bool)$p['can_crear'],
                    'editar' => (bool)$p['can_editar'],
                    'eliminar' => (bool)$p['can_eliminar']
                ];
            }, $permisos)
        ];
    }

    public static function toList($dataList) {
        return array_map(function($item) {
            return self::toArray($item, $item['permisos'] ?? []);
        }, $dataList);
    }
}
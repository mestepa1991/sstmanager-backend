<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Permiso", title="Matriz de Permisos")
 */
class PermisoSerializer {

    public static function toArray($p) {
        if (!$p) return null;

        return [
            'id_modulo' => (int)$p['id_modulo'],
            'modulo'    => $p['nombre_modulo'] ?? 'Módulo ' . $p['id_modulo'],
            'ver'       => (int)($p['can_ver'] ?? 0),
            'crear'     => (int)($p['can_crear'] ?? 0),
            'editar'    => (int)($p['can_editar'] ?? 0),
            'eliminar'  => (int)($p['can_eliminar'] ?? 0)
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
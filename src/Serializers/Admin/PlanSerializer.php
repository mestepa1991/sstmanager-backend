<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Plan", title="Plan de Suscripción")
 */
class PlanSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_plan;

    /** @OA\Property(type="string", example="Profesional") */
    public $nombre_plan;

    public static function toArray($data) {
        if (!$data) return null;
        
        return [
            'id_plan'         => (int)$data['id_plan'],
            'nombre_plan'     => $data['nombre_plan'],
            'descripcion'     => $data['descripcion'] ?? '',
            'limite_usuarios' => (int)$data['limite_usuarios'],
            'precio_mensual'  => (float)($data['precio_mensual'] ?? 0),
            'estado'          => (int)($data['estado'] ?? 1),
            'etiqueta_limite' => (int)$data['limite_usuarios'] === 0 ? 'Ilimitado' : $data['limite_usuarios'] . ' Usuarios'
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Plan",
 * title="Plan de Suscripción",
 * description="Define los límites comerciales y módulos permitidos para una empresa"
 * )
 */
class PlanSerializer {

    public static function toArray($data, $modulos = []) {
        if (!$data) return null;

        return [
            'id' => (int)$data['id_plan'],
            'nombre' => $data['nombre_plan'],
            'descripcion' => $data['descripcion'] ?? '',
            'configuracion' => [
                'limite_usuarios' => (int)$data['limite_usuarios'] === 0 ? 'Ilimitado' : (int)$data['limite_usuarios'],
                'precio_mensual' => (float)$data['precio_mensual'],
                'estado' => (bool)($data['estado'] ?? 1)
            ],
            'modulos_permitidos' => array_map(function($m) {
                return [
                    'id' => (int)$m['id_modulo'],
                    'nombre' => $m['nombre_modulo']
                ];
            }, $modulos)
        ];
    }

    public static function toList($dataList) {
        // En una lista simple, a veces no queremos traer todos los módulos 
        // para ahorrar ancho de banda, pero aquí los incluiremos si vienen en la data
        return array_map(function($item) {
            return self::toArray($item, $item['modulos'] ?? []);
        }, $dataList);
    }
}
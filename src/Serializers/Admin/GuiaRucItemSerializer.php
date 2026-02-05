<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="GuiaRucItemSerializer",
 * title="Serializador de Ítems Guía RUC",
 * description="Transforma los datos de los ítems de la Guía RUC para la API",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="id_categoria", type="integer", example=5),
 * @OA\Property(property="categoria_info", type="object",
 * @OA\Property(property="codigo", type="string", example="CAT-01"),
 * @OA\Property(property="nombre", type="string", example="LIDERAZGO Y COMPROMISO")
 * ),
 * @OA\Property(property="num_item", type="string", example="1.1.1"),
 * @OA\Property(property="requisito", type="string", example="Política de SSTA"),
 * @OA\Property(property="descripcion", type="string", example="La empresa debe contar con una política..."),
 * @OA\Property(property="observaciones", type="string", example="Pendiente revisión anual"),
 * @OA\Property(property="estado", type="integer", example=1)
 * )
 */
class GuiaRucItemSerializer {

    /**
     * Serializa un solo ítem de la Guía RUC
     */
    public static function toArray($item) {
        return [
            'id'             => (int) $item['id'],
            'id_categoria'   => (int) $item['id_categoria'],
            // Incluimos información de la categoría si viene del JOIN
            'categoria_info' => [
                'codigo' => $item['cat_codigo'] ?? null,
                'nombre' => $item['cat_nombre'] ?? null
            ],
            'num_item'       => $item['num_item'],
            'requisito'      => $item['requisito'],
            'descripcion'    => $item['descripcion'],
            'observaciones'  => $item['observaciones'],
            'estado'         => (int) $item['estado']
        ];
    }

    /**
     * Serializa una lista de ítems (útil para la tabla principal)
     */
    public static function listToArray($data) {
        return array_map(fn($item) => self::toArray($item), $data);
    }
}
<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="CategoriaGuiaRucSerializer",
 * title="Serializador de Categoría Guía RUC",
 * description="Transforma los datos de la tabla categorias_guia_ruc para la API",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="codigo", type="string", example="CAT-01"),
 * @OA\Property(property="descripcion", type="string", example="LIDERAZGO Y COMPROMISO"),
 * @OA\Property(property="estado", type="integer", example=1)
 * )
 */
class CategoriaguiarucSerializer {

    /**
     * Serializa un solo objeto de categoría con sus detalles
     */
    public static function toArray($categoria) {
        return [
            'id'          => (int) $categoria['id'],
            'codigo'      => $categoria['codigo'],
            'descripcion' => $categoria['descripcion'],
            'estado'      => (int) $categoria['estado']
        ];
    }

    /**
     * Serializa una lista completa de categorías (útil para el índice o selects)
     */
    public static function listToArray($data) {
        return array_map(fn($item) => [
            'id'          => (int) $item['id'],
            'codigo'      => $item['codigo'],
            'descripcion' => $item['descripcion'],
            'estado'      => (int) $item['estado']
        ], $data);
    }
}
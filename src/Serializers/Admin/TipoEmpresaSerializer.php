<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="TipoEmpresa",
 * title="Configuración de Tipo de Empresa",
 * description="Esquema para la clasificación de empresas por tamaño y rango de empleados"
 * )
 */
class TipoEmpresaSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_config;

    /** @OA\Property(type="string", enum={"Micro", "Pequeña", "Mediana", "Grande"}, example="Micro") */
    public $tamano_empresa;

    /** @OA\Property(type="integer", example=1, description="Límite inferior de empleados") */
    public $empleados_desde;

    /** @OA\Property(type="integer", example=10, description="Límite superior de empleados") */
    public $empleados_hasta;

    /** @OA\Property(type="integer", example=1, description="1: Activo, 0: Inactivo") */
    public $estado;

    /** @OA\Property(type="string", example="2023-10-27 10:00:00") */
    public $created_at;

    /**
     * Mapea los datos de la base de datos a un formato amigable para el Frontend
     */
    public static function toArray($data) {
        if (!$data) return null;

        return [
            'id_config'       => (int)$data['id_config'],
            'tamano_empresa'  => $data['tamano_empresa'],
            'empleados_desde' => (int)$data['empleados_desde'],
            'empleados_hasta' => (int)$data['empleados_hasta'],
            
            // Estado formateado
            'estado'          => (int)$data['estado'],
            'activo'          => (bool)$data['estado'], // Facilita el manejo del Switch en el frontend

            // Campo calculado para etiquetas visuales (opcional)
            'rango_texto'     => "De {$data['empleados_desde']} a {$data['empleados_hasta']} empleados",
            'created_at'      => $data['created_at'] ?? null
        ];
    }

    /**
     * Convierte una lista de registros
     */
    public static function toList($dataList) {
        if (!is_array($dataList)) return [];
        return array_map([self::class, 'toArray'], $dataList);
    }
}
<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="PersonalSst", title="Personal SST Asociado")
 */
class PersonalSstSerializer {

    public static function toArray($data) {
        if (!$data) return null;

        return [
            'id' => (int)$data['id_personal_sst'],
            'empresa' => [
                'id' => (int)$data['id_empresa'],
                'nombre' => $data['nombre_empresa'] ?? 'No definida'
            ],
            'gestion' => [
                'proyecto_rige' => $data['proyecto_rige'], // Campo "PROYECTO RIGE"
            ],
            'profesional' => [
                'nombre' => $data['nombre_profesional'], // Campo "PROFESIONAL SST"
                'correo' => $data['correo_sst'],       // Campo "CORREO SST"
                'telefono' => $data['telefono_sst'] ?? null, // Campo "TELÉFONO SST"
                'firma' => $data['firma_sst_url'] ?? null    // Campo "FIRMA SST"
            ],
            'configuracion' => [
                'activo' => (bool)($data['estado'] ?? 1),
                'registrado_el' => $data['fecha_registro'] ?? null
            ]
        ];
    }

    /**
     * Convierte una lista de registros de Personal SST
     */
    public static function toList($dataList) {
        if (!is_array($dataList)) return [];
        return array_map([self::class, 'toArray'], $dataList);
    }
}
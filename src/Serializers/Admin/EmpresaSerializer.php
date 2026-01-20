<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Empresa", title="Empresa Cliente (Tenant)")
 */
class EmpresaSerializer {

    public static function toArray($data) {
        if (!$data) return null;

        return [
            'id' => (int)$data['id_empresa'],
            'nombre' => $data['nombre_empresa'],
            'nit' => $data['nit'],
            'contacto' => [
                'email' => $data['email_contacto'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null
            ],
            'suscripcion' => [
                'id_plan' => (int)$data['id_plan'],
                'nombre_plan' => $data['nombre_plan'] ?? 'No definido'
            ],
            'configuracion' => [
                'logo' => $data['logo_url'] ?? null,
                'activo' => (bool)($data['estado'] ?? 1),
                'desde' => $data['fecha_registro'] ?? null
            ]
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
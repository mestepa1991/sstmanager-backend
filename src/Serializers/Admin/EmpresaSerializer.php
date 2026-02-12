<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

class EmpresaSerializer {

    public static function toArray($data) {
        if (!$data) return null;

        $numeroDocumento = $data['numero_documento'] ?? ($data['nit'] ?? null);

        return [
            'id' => (int)$data['id_empresa'],
            'nombre' => $data['nombre_empresa'],
            'identificacion' => [
                'tipo' => $data['tipo_documento'] ?? 'NIT',
                'numero' => $numeroDocumento
            ],
            'contacto' => [
                'email' => $data['email_contacto'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null
            ],
            'representante_legal' => [
                'nombre' => $data['nombre_rl'] ?? null,
                'documento' => $data['documento_rl'] ?? null
            ],
            'distribucion_trabajadores' => [
                'directos' => (int)($data['cant_directos'] ?? 0),
                'contratistas' => (int)($data['cant_contratistas'] ?? 0),
                'aprendices' => (int)($data['cant_aprendices'] ?? 0),
                'brigadistas' => (int)($data['cant_brigadistas'] ?? 0),
                'total' => (int)(
                    ($data['cant_directos'] ?? 0) +
                    ($data['cant_contratistas'] ?? 0) +
                    ($data['cant_aprendices'] ?? 0)
                )
            ],
            'suscripcion' => [
                'id_plan' => (int)($data['id_plan'] ?? 0),
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

<?php
namespace App\Serializers\Auth;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Usuario",
 * title="Modelo de Usuario",
 * description="Esquema que representa a un usuario dentro del ecosistema Multi-tenant",
 * required={"nombre", "apellido", "email", "tipo_documento", "numero_documento", "rol", "id_perfil"}
 * )
 */
class UsuarioSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_usuario;

    /** @OA\Property(type="string", example="Juan") */
    public $nombre;

    /** @OA\Property(type="string", example="Pérez") */
    public $apellido;

    /** @OA\Property(type="string", format="email", example="juan.perez@sst.com") */
    public $email; // Nueva propiedad para Swagger y validación

    /** @OA\Property(type="string", example="CC") */
    public $tipo_documento;

    /** @OA\Property(type="string", example="123456789") */
    public $numero_documento;

    /** * @OA\Property(
     * type="string", 
     * enum={"master", "administrador-cliente", "usuario", "soporte"}, 
     * example="administrador-cliente"
     * ) 
     */
    public $rol;

    /** @OA\Property(type="integer", description="ID de la empresa (null si es master o soporte)", example=1, nullable=true) */
    public $id_empresa;

    /** @OA\Property(type="integer", description="ID del perfil de permisos asociado", example=2) */
    public $id_perfil;

    /** @OA\Property(type="string", description="Contraseña (solo para envío en POST/PUT)", example="password123", writeOnly=true) */
    public $password;

    /** @OA\Property(type="integer", example=1, description="1: Activo, 0: Inactivo") */
    public $estado;

    /**
     * Convierte los datos de la base de datos a un formato JSON estructurado.
     */
    public static function toArray($data) {
        if (!$data) return null;

        return [
            'id' => (int)$data['id_usuario'],
            'datos_personales' => [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'nombre_completo' => $data['nombre'] . ' ' . $data['apellido'],
                'correo' => $data['email'] // Mapeo del nuevo campo
            ],
            'identificacion' => [
                'tipo' => $data['tipo_documento'] ?? 'CC',
                'numero' => $data['numero_documento']
            ],
            'seguridad' => [
                'rol_sistema' => $data['rol'],
                'perfil' => [
                    'id' => (int)$data['id_perfil'],
                    'nombre' => $data['nombre_perfil'] ?? 'Sin asignar'
                ]
            ],
            'organizacion' => [
                'id_empresa' => $data['id_empresa'] ? (int)$data['id_empresa'] : null,
                'nombre_empresa' => $data['nombre_empresa'] ?? ' '
            ],
            'estado_cuenta' => [
                'activo' => (bool)($data['estado'] ?? 1),
                'creado_el' => $data['fecha_creacion'] ?? null
            ]
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
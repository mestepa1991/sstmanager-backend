<?php
namespace App\Serializers\Admin;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Modulo",
 * title="Módulo del Sistema",
 * description="Esquema para la gestión de módulos y funciones (Jerarquía)"
 * )
 */
class ModuloSerializer {

    /** @OA\Property(type="integer", example=1) */
    public $id_modulo;

    /** @OA\Property(type="integer", example=1, nullable=true, description="ID del padre. Si es NULL es un Módulo principal.") */
    public $id_padre;

    /** @OA\Property(type="string", example="Usuarios") */
    public $nombre_modulo;

    /** @OA\Property(type="string", example="Gestión de acceso") */
    public $descripcion;

    /** @OA\Property(type="string", example="fas fa-users") */
    public $icono;
    
    /** @OA\Property(type="integer", example=1, description="1: Activo, 0: Inactivo") */
    public $estado;

    /** @OA\Property(type="string", example="Modulo", description="Calculado: Modulo o Funcion") */
    public $tipo_elemento;
    
    public static function toArray($data) {
        if (!$data) return null;

        return [
            // Mapeo directo de la BD al Frontend
            'id_modulo'     => (int)$data['id_modulo'], // Usamos id_modulo para ser explícitos
            'id_padre'      => isset($data['id_padre']) && $data['id_padre'] > 0 ? (int)$data['id_padre'] : null,
            'nombre_modulo' => $data['nombre_modulo'],
            'descripcion'   => $data['descripcion'] ?? '',
            'icono'         => $data['icono'] ?? 'fas fa-cube', // Valor por defecto si viene null
            
            // Estado formateado
            'estado'        => (int)$data['estado'], 
            'activo'        => (bool)$data['estado'], // Útil para interruptores booleanos (true/false)

            // CAMPO CALCULADO (Ayuda visual para el frontend)
            // Si no tiene padre, es "Modulo". Si tiene padre, es "Funcion".
            'tipo'          => (empty($data['id_padre']) || $data['id_padre'] == 0) ? 'Modulo' : 'Funcion'
        ];
    }

    public static function toList($dataList) {
        return array_map([self::class, 'toArray'], $dataList);
    }
}
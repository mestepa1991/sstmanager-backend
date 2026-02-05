<?php
namespace App\Serializers\Admin;

class CalificacionSerializer {

    /**
     * Formatea una calificación individual, opcionalmente con sus detalles
     */
    public static function toArray($calificacion, $detalles = []) {
        $response = [
            // 🔥 MISMO NOMBRE QUE EN BD
            'id_calificacion' => (int) $calificacion['id_calificacion'],
            'nombre'          => (string) $calificacion['nombre'],
            'estado'          => $calificacion['estado']
        ];

        if (!empty($detalles)) {
            $response['items'] = array_map(function($d) {
                return [
                    'id_detalle'  => (int) $d['id_detalle'],
                    'descripcion' => $d['descripcion'],
                    'valor'       => (float) $d['valor'],
                    'estado'      => $d['estado']
                ];
            }, $detalles);
        }

        return $response;
    }

    /**
     * Lista de calificaciones
     */
    public static function toArrayMany(array $calificaciones): array {
        return array_map(
            fn($c) => self::toArray($c),
            $calificaciones
        );
    }
}

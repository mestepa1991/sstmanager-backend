<?php
namespace App\Serializers\Admin;

class CalificacionSerializer {

    /**
     * Formatea una calificación individual, opcionalmente con sus detalles
     */
    public static function toArray($calificacion, $detalles = []) {
        $response = [
            'id'     => (int) $calificacion['id_calificacion'],
            'nombre' => (string) $calificacion['nombre'],
            'estado' => $calificacion['estado']
        ];

        // Si enviamos detalles, los formateamos y los incrustamos
        if (!empty($detalles)) {
            $response['items'] = array_map(function($d) {
                return [
                    'id_detalle'  => (int) $d['id_detalle'],
                    'descripcion' => $d['descripcion'],
                    'valor'       => (float) $d['valor']
                ];
            }, $detalles);
        }

        return $response;
    }

    public static function toArrayMany($calificaciones) {
        return array_map(fn($c) => self::toArray($c), $calificaciones);
    }
}
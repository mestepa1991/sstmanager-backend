<?php
namespace App\Serializers\Admin;

class CicloSerializer {

    /**
     * Formatea un solo ciclo
     */
    public static function toArray($ciclo) {
        return [
            'id'     => (int) $ciclo['id_ciclo'],
            'nombre' => (string) $ciclo['nombre']
        ];
    }

    /**
     * Formatea una lista de ciclos
     */
    public static function toArrayMany($ciclos) {
        $data = [];
        foreach ($ciclos as $ciclo) {
            $data[] = self::toArray($ciclo);
        }
        return $data;
    }
}
<?php
namespace App\Serializers\Admin;

class ItemSerializer {

    /**
     * Serializa un solo registro de la base de datos.
     *
     * @param array|object|null $data Fila de la base de datos
     * @return array|null Estructura limpia para JSON
     */
    public static function format($data) {
        // Si no hay datos, retornamos null
        if (empty($data)) {
            return null;
        }

        // Convertimos a array si viene como objeto (stdClass)
        $data = (array) $data;

        return [
            // Mapeo de ID (Primary Key)
            'id' => (int) $data['id_detalle'],

            // Relación (Foreign Key) - Casteamos a entero
            'idCategoria' => (int) $data['id_categorias'],

            // Campos de texto
            'itemEstandar' => (string) $data['item_estandar'],
            'item' => (string) $data['item'],
            'criterio' => (string) $data['criterio'],
            'modoVerificacion' => (string) $data['modo_verificacion'],
            
            // Estado y Metadatos
            'estado' => (string) $data['estado'],
            'fechaCreacion' => $data['fecha_creacion'],
        ];
    }

    /**
     * Serializa una lista completa de registros.
     *
     * @param array $list Array de filas de la base de datos
     * @return array Array de objetos serializados
     */
    public static function formatList($list) {
        if (empty($list)) {
            return [];
        }

        // Aplicamos la función format a cada elemento del array
        return array_map([self::class, 'format'], $list);
    }
}
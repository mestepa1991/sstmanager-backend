<?php
namespace App\Serializers\Admin;

class CategoriaSerializer {
    public static function toArray($categoria, $tipos = []) {
        return [
            'id' => (int) $categoria['id'],
            'descripcion' => $categoria['descripcion'],
            'estado' => (int) $categoria['estado'],
            'tipos' => array_map(fn($t) => [
                'id' => (int) $t['id'],
                'descripcion' => $t['descripcion'],
                'estado' => (int) $t['estado']
            ], $tipos)
        ];
    }

    public static function listToArray($data) {
        return array_map(fn($item) => [
            'id' => (int) $item['id'],
            'descripcion' => $item['descripcion'],
            'estado' => (int) $item['estado']
        ], $data);
    }
}
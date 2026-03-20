<?php
namespace App\Serializers\Sst;

class FormularioSerializer {

    public static function format($personalizado, $plantillaMaestra, $id_item) {
        
        // 1. Si existe personalización de la empresa, usamos esa.
        // 2. Si no, usamos la plantilla maestra de la BD.
        // 3. Si no hay ninguna, enviamos un objeto vacío.
        
        $datosFinales = null;

        if (!empty($personalizado)) {
            $datosFinales = json_decode($personalizado['datos_json'], true);
            $fuente = 'empresa';
        } elseif (!empty($plantillaMaestra)) {
            $datosFinales = json_decode($plantillaMaestra, true);
            $fuente = 'base_legal';
        }

        return [
            'idItem' => (int) $id_item,
            'origen' => $fuente ?? 'vacio',
            'campos' => $datosFinales ?? [],
            'editadoPor' => isset($personalizado['usuario_editor']) ? (int)$personalizado['usuario_editor'] : null,
            'fecha' => $personalizado['fecha_actualizacion'] ?? null
        ];
    }
}
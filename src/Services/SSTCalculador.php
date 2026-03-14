<?php

namespace App\Services;

/**
 * Clase encargada de la lógica legal según la Resolución 0312 de 2019.
 * Filtra los 62 ítems maestros para asignar solo los obligatorios.
 */
class SSTCalculador
{
    // IDs oficiales para el estándar de 7 ítems (Riesgo 1, 2, 3 y <= 10 empleados)
    private array $items7 = ['1.1.1', '1.1.4', '1.2.1', '2.1.1', '3.1.1', '4.1.1', '4.2.1'];

    // IDs oficiales para el estándar de 21 ítems (Riesgo 1, 2, 3 y 11-50 empleados)
    private array $items21 = [
        '1.1.1', '1.1.3', '1.1.4', '1.1.6', '1.1.8', '1.2.1', '2.1.1', 
        '2.4.1', '2.5.1', '3.1.1', '3.1.2', '3.1.4', '3.1.7', '3.1.9', 
        '3.2.1', '3.3.1', '4.1.2', '4.2.1', '4.2.2', '5.1.1', '6.1.1'
    ];

    /**
     * Aplica el filtrado legal.
     * @param array $todosLosItems El array de 62 registros de la base de datos.
     */
    public function obtenerItemsLegales(array $todosLosItems, int $numEmpleados, int $riesgo): array
    {
        // REGLA DE ORO: Si es Riesgo 4 o 5, o más de 50 empleados -> Estándar Completo (62)
        if ($riesgo >= 4 || $numEmpleados > 50) {
            return [
                'categoria' => 'Estándares Mínimos Completos (62 ítems)',
                'codigo_std' => 62,
                'items' => $todosLosItems
            ];
        }

        // Caso 21 Ítems
        if ($numEmpleados >= 11 && $numEmpleados <= 50) {
            return [
                'categoria' => 'Estándares Mínimos (21 ítems)',
                'codigo_std' => 21,
                'items' => $this->filtrar($todosLosItems, $this->items21)
            ];
        }

        // Caso 7 Ítems
        if ($numEmpleados <= 10) {
            return [
                'categoria' => 'Estándares Mínimos (7 ítems)',
                'codigo_std' => 7,
                'items' => $this->filtrar($todosLosItems, $this->items7)
            ];
        }

        return ['categoria' => 'No definido', 'codigo_std' => 0, 'items' => []];
    }

   private function filtrar(array $items, array $permisos): array
    {
        $filtrados = [];
        foreach ($items as $item) {
            $idItem = $item['item_estandar'] ?? ''; 
            
            // FILTRO EXTREMO: Elimina todo lo que NO sea un número o un punto.
            // Si llega " 1. 1 .1. " lo convierte a "1.1.1."
            $cleanIdItem = preg_replace('/[^0-9.]/', '', $idItem);
            
            // Quita los puntos al principio o al final ("1.1.1." -> "1.1.1")
            $cleanIdItem = trim($cleanIdItem, '.'); 
            
            if (in_array($cleanIdItem, $permisos)) {
                $filtrados[] = $item;
            }
        }
        return $filtrados;
    }
}
<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CalificacionModel;
use App\Serializers\Admin\CalificacionSerializer;
use OpenApi\Annotations as OA;
use Exception;

class CalificacionController {
    private $model;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new CalificacionModel($db);
    }

   public function getAll() {
    try {
        // Trae PADRE + DETALLE (si existe)
        $sql = "
            SELECT 
                c.id_calificacion,
                c.nombre AS calificacion,
                c.estado AS estado_calificacion,

                d.id_detalle,
                d.descripcion,
                d.valor,
                d.estado AS estado_detalle

            FROM calificaciones c
            LEFT JOIN calificaciones_detalles d 
                ON d.id_calificacion = c.id_calificacion
                AND d.estado = 'Activo'
            WHERE c.estado = 'Activo'
            ORDER BY c.id_calificacion DESC, d.valor DESC
        ";

        $rows = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Agrupar en formato:
        // [{id, nombre, estado, items:[{id_detalle, descripcion, valor, estado}]}]
        $map = [];
        foreach ($rows as $r) {
            $id = (int)$r['id_calificacion'];

            if (!isset($map[$id])) {
                $map[$id] = [
                    "id" => $id,
                    "nombre" => $r["calificacion"],
                    "estado" => $r["estado_calificacion"],
                    "items" => []
                ];
            }

            // Si hay detalle, lo agregamos
            if (!empty($r["id_detalle"])) {
                $map[$id]["items"][] = [
                    "id_detalle" => (int)$r["id_detalle"],
                    "descripcion" => $r["descripcion"],
                    "valor" => (float)$r["valor"],
                    "estado" => $r["estado_detalle"]
                ];
            }
        }

        return json_encode(array_values($map));

    } catch (\Exception $e) {
        http_response_code(500);
        return json_encode(["error" => $e->getMessage()]);
    }
}


    public function getOne($id) {
        $calificacion = $this->model->find($id);

        if (!$calificacion || ($calificacion['estado'] ?? '') === 'Inactivo') {
            http_response_code(404);
            return json_encode(["error" => "Calificación no encontrada o eliminada"]);
        }

        $detalles = $this->model->getDetallesActivos((int)$id);

        return json_encode(CalificacionSerializer::toArray($calificacion, $detalles));
    }

    public function create($input) {
    if (empty($input['nombre'])) {
        http_response_code(400);
        return json_encode(["error" => "El nombre es obligatorio"]);
    }

    // 1) Crear padre
    $id = $this->model->create([
        'nombre' => $input['nombre'],
        'estado' => $input['estado'] ?? 'Activo'
    ]);

    if (!$id) {
        http_response_code(500);
        return json_encode(["error" => "Error al crear"]);
    }

    // 2) Si vienen items, insertar detalle(s)
    if (!empty($input['items']) && is_array($input['items'])) {
        $sqlDetalle = "INSERT INTO calificaciones_detalles (id_calificacion, descripcion, valor, estado)
                       VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sqlDetalle);

        foreach ($input['items'] as $it) {
            $desc = trim($it['descripcion'] ?? '');
            $valor = $it['valor'] ?? null;
            $estadoDet = $it['estado'] ?? 'Activo';

            if ($desc === '' || $valor === null || $valor === '') continue;

            $stmt->execute([$id, $desc, $valor, $estadoDet]);
        }
    }

    http_response_code(201);
    return json_encode(["mensaje" => "Calificación creada", "id" => $id]);
}


    public function update($id, $input) {
        if (empty($input['nombre'])) {
            http_response_code(400);
            return json_encode(["error" => "Datos incompletos"]);
        }

        $success = $this->model->update($id, ['nombre' => $input['nombre']]);
        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "No hubo cambios"]);
    }

    public function delete($id) {
        $sql = "UPDATE calificaciones SET estado = 'Inactivo' WHERE id_calificacion = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success) {
            $this->db->prepare("UPDATE calificaciones_detalles SET estado = 'Inactivo' WHERE id_calificacion = ?")->execute([$id]);
            return json_encode(["mensaje" => "Registro marcado como inactivo correctamente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al procesar la solicitud"]);
    }
}

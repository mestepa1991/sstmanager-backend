<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CalificacionModel;
use App\Serializers\Admin\CalificacionSerializer;
use OpenApi\Annotations as OA;
use Exception;

/**
 * @OA\Schema(
 * schema="Calificacion",
 * title="Calificación",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="nombre", type="string", example="Escala SST"),
 * @OA\Property(property="estado", type="integer", example=1),
 * @OA\Property(
 * property="items",
 * type="array",
 * @OA\Items(
 * @OA\Property(property="id_detalle", type="integer"),
 * @OA\Property(property="descripcion", type="string"),
 * @OA\Property(property="valor", type="number")
 * )
 * )
 * )
 */
class CalificacionController {
    private $model;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new CalificacionModel($db);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=calificaciones",
     * tags={"Admin - Calificaciones"},
     * summary="Listar todas las escalas de calificación activas",
     * @OA\Response(response=200, description="Lista obtenida", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Calificacion")))
     * )
     */
    public function getAll() {
        try {
            // Solo traemos las que están en estado 'Activo'
            $sql = "SELECT * FROM calificaciones WHERE estado = 'Activo' ORDER BY id_calificacion DESC";
            $data = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            return json_encode(CalificacionSerializer::toArrayMany($data));
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     * path="/index.php?table=calificaciones&id={id}",
     * tags={"Admin - Calificaciones"},
     * summary="Obtener una calificación con sus detalles",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Objeto completo", @OA\JsonContent(ref="#/components/schemas/Calificacion")),
     * @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function getOne($id) {
        $calificacion = $this->model->find($id);
        
        if (!$calificacion || $calificacion['estado'] === 'Inactivo') {
            http_response_code(404);
            return json_encode(["error" => "Calificación no encontrada o eliminada"]);
        }

        $stmt = $this->db->prepare("SELECT * FROM calificaciones_detalles WHERE id_calificacion = ? AND estado = 'Activo' ORDER BY valor DESC");
        $stmt->execute([$id]);
        $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(CalificacionSerializer::toArray($calificacion, $detalles));
    }

    /**
     * @OA\Post(
     * path="/index.php?table=calificaciones",
     * tags={"Admin - Calificaciones"},
     * summary="Crear nueva escala de calificación",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(@OA\Property(property="nombre", type="string", example="Evaluación Anual"))
     * ),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        if (empty($input['nombre'])) {
            http_response_code(400);
            return json_encode(["error" => "El nombre es obligatorio"]);
        }

        $id = $this->model->create(['nombre' => $input['nombre'], 'estado' => 'Activo']);
        
        if ($id) {
            http_response_code(201);
            return json_encode(["mensaje" => "Calificación creada", "id" => $id]);
        }
        
        http_response_code(500);
        return json_encode(["error" => "Error al crear"]);
    }

    /**
     * @OA\Put(
     * path="/index.php?table=calificaciones&id={id}",
     * tags={"Admin - Calificaciones"},
     * summary="Actualizar nombre de la calificación",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="nombre", type="string"))),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        if (empty($input['nombre'])) {
            http_response_code(400);
            return json_encode(["error" => "Datos incompletos"]);
        }

        $success = $this->model->update($id, ['nombre' => $input['nombre']]);
        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "No hubo cambios"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=calificaciones&id={id}",
     * tags={"Admin - Calificaciones"},
     * summary="Eliminar calificación (Borrado Lógico)",
     * description="No elimina el registro de la BD, solo cambia el estado a 'Inactivo'.",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado")
     * )
     */
    public function delete($id) {
        // --- AQUÍ ESTÁ LA LÓGICA DE BORRADO LÓGICO ---
        // En lugar de llamar a $this->model->delete($id), hacemos un UPDATE del estado
        $sql = "UPDATE calificaciones SET estado = 'Inactivo' WHERE id_calificacion = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success) {
            // Opcional: También podrías inactivar los detalles asociados
            $this->db->prepare("UPDATE calificaciones_detalles SET estado = 'Inactivo' WHERE id_calificacion = ?")->execute([$id]);
            
            return json_encode(["mensaje" => "Registro marcado como inactivo correctamente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al procesar la solicitud"]);
    }
}
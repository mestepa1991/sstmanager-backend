<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PlanModel;
use App\Serializers\Admin\PlanSerializer;
use Exception;

class PlanController extends GenericController {
  
    public function __construct($db) {
        parent::__construct($db, 'planes');
        $this->model = new PlanModel($db); 
    }

    /**
     * @OA\Get(
     * path="/planes",
     * operationId="getPlanesList",
     * tags={"Admin - Planes"},
     * summary="Listar todos los planes",
     * @OA\Response( 
     * response=200, 
     * description="Exitoso",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Plan"))
     * )
     * )
     */
    public function getAll() {
        $data = $this->model->all();
        return json_encode(PlanSerializer::toList($data));
    }

    /**
     * @OA\Post(
     * path="/planes",
     * operationId="createPlan",
     * tags={"Admin - Planes"},
     * summary="Crear un nuevo plan comercial",
     * @OA\RequestBody( 
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_plan"},
     * @OA\Property(property="nombre_plan", type="string", example="Plan Pro"),
     * @OA\Property(property="descripcion", type="string", example="Acceso a módulos avanzados"),
     * @OA\Property(property="limite_usuarios", type="integer", example=20),
     * @OA\Property(property="precio_mensual", type="number", example=59.90)
     * )
     * ),
     * @OA\Response(response=201, description="Creado"),
     * @OA\Response(response=400, description="Error de validación")
     * )
     */
    public function create($input)  {
        try {
            if (empty($input['nombre_plan'])) {
                throw new Exception("El nombre del plan es obligatorio");
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Plan registrado correctamente"]);

        } catch (Exception $e) { 
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/planes/{id}",
     * operationId="updatePlan",
     * tags={"Admin - Planes"},
     * summary="Actualizar plan",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_plan", type="string"),
     * @OA\Property(property="descripcion", type="string"),
     * @OA\Property(property="estado", type="integer")
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado"),
     * @OA\Response(response=400, description="Error en actualización")
     * )
     */
   public function update($id, $input) {
    try {
        // Aseguramos que solo se envíen campos que existan en la tabla planes
        $success = $this->model->update($id, $input);
        
        if (!$success) {
            http_response_code(500);
            return json_encode([
                "ok" => false, 
                "error" => "No se pudo actualizar. Verifique que los campos coincidan con la base de datos."
            ]);
        }

        return json_encode([
            "ok" => true, 
            "mensaje" => "Plan actualizado correctamente",
            "filas_afectadas" => $success
        ]);

    } catch (Exception $e) { 
        http_response_code(400);
        return json_encode(["error" => $e->getMessage()]);
    }
   }

    /**
     * @OA\Delete(
     * path="/planes/{id}",
     * operationId="deletePlan",
     * tags={"Admin - Planes"},
     * summary="Desactivar plan", 
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Desactivado")
     * )
     */
    public function delete($id) {
        // Validación de seguridad: No desactivar si hay empresas vinculadas (opcional pero recomendado)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE id_plan = ? AND estado = 1");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            return json_encode(["error" => "No se puede desactivar un plan con empresas activas"]);
        }

        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Plan comercial desactivado"]);
    }
}
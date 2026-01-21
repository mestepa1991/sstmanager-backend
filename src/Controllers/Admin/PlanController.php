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
     * path="/index.php?table=planes",
     * tags={"Admin"},
     * summary="Listar todos los planes con sus módulos permitidos",
     * @OA\Response(
     * response=200, 
     * description="Lista de planes comerciales",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Plan"))
     * )
     * )
     */
    public function getAll() {
        $planes = $this->model->all();
        $resultado = [];

        foreach ($planes as $plan) {
            // Buscamos los módulos asociados a este plan en la tabla intermedia
            $sql = "SELECT m.id_modulo, m.nombre_modulo 
                    FROM modulos m 
                    INNER JOIN plan_modulos pm ON m.id_modulo = pm.id_modulo 
                    WHERE pm.id_plan = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$plan['id_plan']]);
            $modulos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $resultado[] = PlanSerializer::toArray($plan, $modulos);
        }

        return json_encode($resultado);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=planes&id={id}",
     * tags={"Admin"},
     * summary="Obtener detalle de un plan específico",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle del plan")
     * )
     */
    public function getOne($id) {
        $plan = $this->model->find($id);
        if (!$plan) {
            http_response_code(404);
            return json_encode(["error" => "Plan no encontrado"]);
        }

        $sql = "SELECT m.id_modulo, m.nombre_modulo 
                FROM modulos m 
                INNER JOIN plan_modulos pm ON m.id_modulo = pm.id_modulo 
                WHERE pm.id_plan = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $modulos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(PlanSerializer::toArray($plan, $modulos));
    }

    /**
     * @OA\Post(
     * path="/index.php?table=planes",
     * tags={"Admin"},
     * summary="Crear un nuevo plan comercial",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_plan", "modulos"},
     * @OA\Property(property="nombre_plan", type="string", example="Plan Pro"),
     * @OA\Property(property="descripcion", type="string", example="Acceso a módulos avanzados"),
     * @OA\Property(property="limite_usuarios", type="integer", example=20),
     * @OA\Property(property="precio_mensual", type="number", example=59.90),
     * @OA\Property(property="modulos", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     * )
     * ),
     * @OA\Response(response=201, description="Plan creado")
     * )
     */
    public function create($input) {
        $this->db->beginTransaction();
        try {
            if (empty($input['nombre_plan'])) throw new Exception("Nombre del plan obligatorio.");

            // 1. Crear registro base
            $idPlan = $this->model->create([
                'nombre_plan' => $input['nombre_plan'],
                'descripcion' => $input['descripcion'] ?? '',
                'limite_usuarios' => $input['limite_usuarios'] ?? 0,
                'precio_mensual' => $input['precio_mensual'] ?? 0
            ]);

            // 2. Asociar módulos en tabla intermedia
            if (isset($input['modulos']) && is_array($input['modulos'])) {
                $stmt = $this->db->prepare("INSERT INTO plan_modulos (id_plan, id_modulo) VALUES (?, ?)");
                foreach ($input['modulos'] as $idModulo) {
                    $stmt->execute([$idPlan, $idModulo]);
                }
            }

            $this->db->commit();
            return json_encode(["id" => $idPlan, "mensaje" => "Plan comercial creado exitosamente"]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/index.php?table=planes&id={id}",
     * tags={"Admin"},
     * summary="Actualización total del plan",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Plan")),
     * @OA\Response(response=200, description="Plan actualizado")
     * )
     */
    public function update($id, $input) {
        $this->db->beginTransaction();
        try {
            // Actualizar datos de la tabla maestra
            $datos = [
                'nombre_plan' => $input['nombre_plan'] ?? null,
                'descripcion' => $input['descripcion'] ?? null,
                'limite_usuarios' => $input['limite_usuarios'] ?? null,
                'precio_mensual' => $input['precio_mensual'] ?? null,
                'estado' => $input['estado'] ?? null
            ];
            $datos = array_filter($datos, fn($v) => $v !== null);
            $this->model->update($id, $datos);

            // Sincronizar módulos (Borrar y Reinsertar)
            if (isset($input['modulos']) && is_array($input['modulos'])) {
                $this->db->prepare("DELETE FROM plan_modulos WHERE id_plan = ?")->execute([$id]);
                $stmt = $this->db->prepare("INSERT INTO plan_modulos (id_plan, id_modulo) VALUES (?, ?)");
                foreach ($input['modulos'] as $idModulo) {
                    $stmt->execute([$id, $idModulo]);
                }
            }

            $this->db->commit();
            return json_encode(["ok" => true, "mensaje" => "Plan y módulos actualizados"]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     * path="/index.php?table=planes&id={id}",
     * tags={"Admin"},
     * summary="Actualización parcial del plan",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function patch($id, $input) {
        return $this->update($id, $input);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=planes&id={id}",
     * tags={"Admin"},
     * summary="Desactivar plan (con validación de uso)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Desactivado")
     * )
     */
    public function delete($id) {
        // Validar si hay empresas activas con este plan
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE id_plan = ? AND estado = 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            return json_encode(["error" => "No se puede desactivar un plan con empresas activas vinculadas."]);
        }

        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Plan comercial inactivado"]);
    }
}
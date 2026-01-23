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
     * summary="Listar planes",
     * description="Ruta real: /index.php?table=planes",
     * @OA\Response(
     * response=200, 
     * description="Lista de planes",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Plan"))
     * )
     * )
     */
    public function getAll() {
        $planes = $this->model->all();
        $resultado = [];
        foreach ($planes as $plan) {
            $stmt = $this->db->prepare("SELECT m.id_modulo, m.nombre_modulo FROM modulos m INNER JOIN plan_modulos pm ON m.id_modulo = pm.id_modulo WHERE pm.id_plan = ?");
            $stmt->execute([$plan['id_plan']]);
            $modulos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $resultado[] = PlanSerializer::toArray($plan, $modulos);
        }
        return json_encode($resultado);
    }

    /**
     * @OA\Get(
     * path="/planes/{id}",
     * operationId="getPlanDetail",
     * tags={"Admin - Planes"},
     * summary="Detalle plan",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle")
     * )
     */
    public function getOne($id) {
        $plan = $this->model->find($id);
        if (!$plan) return json_encode(["error" => "No encontrado"]);
        
        $stmt = $this->db->prepare("SELECT m.id_modulo, m.nombre_modulo FROM modulos m INNER JOIN plan_modulos pm ON m.id_modulo = pm.id_modulo WHERE pm.id_plan = ?");
        $stmt->execute([$id]);
        $modulos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return json_encode(PlanSerializer::toArray($plan, $modulos));
    }

    /**
     * @OA\Post(
     * path="/planes",
     * operationId="createPlan",
     * tags={"Admin - Planes"},
     * summary="Crear plan",
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Plan")),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        $this->db->beginTransaction();
        try {
            if (empty($input['nombre_plan'])) throw new Exception("Nombre obligatorio");
            
            $idPlan = $this->model->create(['nombre_plan' => $input['nombre_plan'], 'descripcion' => $input['descripcion'] ?? '', 'limite_usuarios' => $input['limite_usuarios'] ?? 0, 'precio_mensual' => $input['precio_mensual'] ?? 0]);
            
            if (!empty($input['modulos'])) {
                $stmt = $this->db->prepare("INSERT INTO plan_modulos (id_plan, id_modulo) VALUES (?, ?)");
                foreach ($input['modulos'] as $idMod) $stmt->execute([$idPlan, $idMod]);
            }
            $this->db->commit();
            return json_encode(["id" => $idPlan, "mensaje" => "Plan creado"]);
        } catch (Exception $e) {
            $this->db->rollBack();
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
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Plan")),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        $this->db->beginTransaction();
        try {
            $this->model->update($id, $input);
            if (isset($input['modulos'])) {
                $this->db->prepare("DELETE FROM plan_modulos WHERE id_plan = ?")->execute([$id]);
                $stmt = $this->db->prepare("INSERT INTO plan_modulos (id_plan, id_modulo) VALUES (?, ?)");
                foreach ($input['modulos'] as $idMod) $stmt->execute([$id, $idMod]);
            }
            $this->db->commit();
            return json_encode(["ok" => true, "mensaje" => "Actualizado"]);
        } catch (Exception $e) {
            $this->db->rollBack();
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/planes/{id}",
     * operationId="deletePlan",
     * tags={"Admin - Planes"},
     * summary="Inactivar plan",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Inactivado")
     * )
     */
    public function delete($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE id_plan = ? AND estado = 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) return json_encode(["error" => "Plan en uso"]);
        
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Plan inactivado"]);
    }
}
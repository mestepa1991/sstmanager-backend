<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\EmpresaModel;
use App\Serializers\Admin\EmpresaSerializer;
use Exception;

class EmpresaController extends GenericController {
  
    public function __construct($db) {
        parent::__construct($db, 'empresas');
        $this->model = new EmpresaModel($db); 
    }

    /**
     * @OA\Get(
     * path="/empresas",
     * operationId="getEmpresasList",
     * tags={"Admin - Empresas"},
     * summary="Listar todas las empresas",
     * description="Ruta real: /index.php?table=empresas",
     * @OA\Response(
     * response=200, 
     * description="Lista de empresas",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Empresa"))
     * )
     * )
     */
    public function getAll() {
        $sql = "SELECT e.*, p.nombre_plan 
                FROM empresas e 
                INNER JOIN planes p ON e.id_plan = p.id_plan 
                WHERE e.estado = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(EmpresaSerializer::toList($data));
    }

    /**
     * @OA\Get(
     * path="/empresas/{id}",
     * operationId="getEmpresaDetail",
     * tags={"Admin - Empresas"},
     * summary="Obtener detalle de empresa",
     * description="Ruta real: /index.php?table=empresas&id={id}",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle")
     * )
     */
    public function getOne($id) {
        $sql = "SELECT e.*, p.nombre_plan FROM empresas e 
                INNER JOIN planes p ON e.id_plan = p.id_plan 
                WHERE e.id_empresa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            http_response_code(404);
            return json_encode(["error" => "Empresa no encontrada"]);
        }

        return json_encode(EmpresaSerializer::toArray($data));
    }

    /**
     * @OA\Post(
     * path="/empresas",
     * operationId="createEmpresa",
     * tags={"Admin - Empresas"},
     * summary="Registrar empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_empresa", "nit", "id_plan"},
     * @OA\Property(property="nombre_empresa", type="string", example="Empresa SAS"),
     * @OA\Property(property="nit", type="string", example="900123456"),
     * @OA\Property(property="id_plan", type="integer", example=1),
     * @OA\Property(property="email_contacto", type="string"),
     * @OA\Property(property="telefono", type="string"),
     * @OA\Property(property="direccion", type="string")
     * )
     * ),
     * @OA\Response(response=201, description="Creada")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['nit']) || empty($input['id_plan'])) throw new Exception("NIT e ID Plan requeridos.");

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ?");
            $stmt->execute([$input['nit']]);
            if ($stmt->fetchColumn() > 0) throw new Exception("El NIT ya existe.");

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Empresa registrada"]);
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/empresas/{id}",
     * operationId="updateEmpresa",
     * tags={"Admin - Empresas"},
     * summary="Actualizar empresa",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Empresa")),
     * @OA\Response(response=200, description="Actualizada")
     * )
     */
    public function update($id, $input) {
        try {
            if (isset($input['nit'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ? AND id_empresa != ?");
                $stmt->execute([$input['nit'], $id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("El NIT ya pertenece a otra empresa.");
            }
            $success = $this->model->update($id, $input);
            return json_encode(["ok" => $success, "mensaje" => "Actualizado correctamente"]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/empresas/{id}",
     * operationId="deleteEmpresa",
     * tags={"Admin - Empresas"},
     * summary="Inactivar empresa",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Inactivada")
     * )
     */
    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Empresa inactivada"]);
    }
}
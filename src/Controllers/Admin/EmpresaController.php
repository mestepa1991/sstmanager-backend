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
     * path="/index.php?table=empresas",
     * tags={"Admin"},
     * summary="Listar todas las empresas con su plan actual",
     * @OA\Response(
     * response=200, 
     * description="Lista de clientes",
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
     * path="/index.php?table=empresas&id={id}",
     * tags={"Admin"},
     * summary="Obtener detalle de una empresa por ID",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle de la empresa")
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
     * path="/index.php?table=empresas",
     * tags={"Admin"},
     * summary="Registrar nueva empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_empresa", "nit", "id_plan"},
     * @OA\Property(property="nombre_empresa", type="string", example="Empresa Ejemplo S.A.S"),
     * @OA\Property(property="nit", type="string", example="900123456-7"),
     * @OA\Property(property="id_plan", type="integer", example=1),
     * @OA\Property(property="email_contacto", type="string", example="admin@empresa.com"),
     * @OA\Property(property="telefono", type="string", example="3001234567"),
     * @OA\Property(property="direccion", type="string", example="Calle 123 # 45-67")
     * )
     * ),
     * @OA\Response(response=201, description="Empresa creada")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['nit']) || empty($input['id_plan'])) {
                throw new Exception("NIT e ID de Plan son obligatorios.");
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ?");
            $stmt->execute([$input['nit']]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409);
                throw new Exception("El NIT ya está registrado en el sistema.");
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Empresa registrada exitosamente"]);
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/index.php?table=empresas&id={id}",
     * tags={"Admin"},
     * summary="Reemplazar datos de la empresa (Actualización Completa)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_empresa", "nit", "id_plan"},
     * @OA\Property(property="nombre_empresa", type="string"),
     * @OA\Property(property="nit", type="string"),
     * @OA\Property(property="id_plan", type="integer"),
     * @OA\Property(property="email_contacto", type="string"),
     * @OA\Property(property="telefono", type="string"),
     * @OA\Property(property="direccion", type="string"),
     * @OA\Property(property="logo_url", type="string"),
     * @OA\Property(property="estado", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        try {
            if (empty($input)) throw new Exception("No se enviaron datos.");

            if (isset($input['nit'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ? AND id_empresa != ?");
                $stmt->execute([$input['nit'], $id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("El NIT enviado ya pertenece a otra empresa.");
                }
            }

            $success = $this->model->update($id, $input);
            return json_encode([
                "ok" => $success, 
                "mensaje" => $success ? "Empresa actualizada" : "Sin cambios realizados"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     * path="/index.php?table=empresas&id={id}",
     * tags={"Admin"},
     * summary="Actualización parcial de empresa",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_empresa", type="string"),
     * @OA\Property(property="id_plan", type="integer"),
     * @OA\Property(property="estado", type="integer")
     * )
     * ),
     * @OA\Response(response=200, description="Campo actualizado")
     * )
     */
    public function patch($id, $input) {
        return $this->update($id, $input);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=empresas&id={id}",
     * tags={"Admin"},
     * summary="Suspender empresa (Soft Delete)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Inactivada")
     * )
     */
    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode([
            "ok" => $success, 
            "mensaje" => $success ? "Empresa suspendida correctamente" : "Error al suspender"
        ]);
    }
}
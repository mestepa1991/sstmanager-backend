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
     * @OA\Response(
     * response=200, 
     * description="Lista de empresas",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Empresa"))
     * )
     * )
     */
    public function getAll() {
        // Ajustamos el SQL para traer los nuevos campos
        $sql = "SELECT e.*, p.nombre_plan 
                FROM empresas e 
                LEFT JOIN planes p ON e.id_plan = p.id_plan 
                WHERE e.estado = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(EmpresaSerializer::toList($data));
    }

    public function getOne($id) {
        $sql = "SELECT e.*, p.nombre_plan FROM empresas e 
                LEFT JOIN planes p ON e.id_plan = p.id_plan 
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
     * @OA\Property(property="nombre_empresa", type="string", example="Mi Empresa S.A.S"),
     * @OA\Property(property="tipo_documento", type="string", example="NIT"),
     * @OA\Property(property="nit", type="string", example="900.000.000-1"),
     * @OA\Property(property="id_plan", type="integer", example=1),
     * @OA\Property(property="email_contacto", type="string", example="contacto@empresa.com"),
     * @OA\Property(property="telefono", type="string", example="601234567"),
     * @OA\Property(property="direccion", type="string", example="Calle 123 #45-67"),
     * @OA\Property(property="nombre_rl", type="string", example="Juan Perez"),
     * @OA\Property(property="documento_rl", type="string", example="10203040"),
     * @OA\Property(property="cant_directos", type="integer", example=10),
     * @OA\Property(property="cant_contratistas", type="integer", example=5),
     * @OA\Property(property="cant_aprendices", type="integer", example=2),
     * @OA\Property(property="cant_brigadistas", type="integer", example=3)
     * )
     * ),
     * @OA\Response(response=201, description="Creada")
     * )
     */
    public function create($input) {
        try {
            // Validaciones básicas
            if (empty($input['nombre_empresa']) || empty($input['nit']) || empty($input['id_plan'])) {
                throw new Exception("Nombre, NIT e ID Plan son obligatorios.");
            }

            // Validar unicidad de NIT
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ?");
            $stmt->execute([$input['nit']]);
            if ($stmt->fetchColumn() > 0) throw new Exception("El NIT ya está registrado.");

            // El GenericModel se encarga de mapear los keys del array $input 
            // con las columnas de la tabla 'empresas'
            $id = $this->model->create($input);
            
            http_response_code(201);
            return json_encode([
                "status" => "success",
                "id" => $id, 
                "mensaje" => "Empresa registrada exitosamente"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function update($id, $input) {
        try {
            if (isset($input['nit'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE nit = ? AND id_empresa != ?");
                $stmt->execute([$input['nit'], $id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("El NIT ya pertenece a otra empresa.");
            }
            
            $success = $this->model->update($id, $input);
            return json_encode(["ok" => $success, "mensaje" => "Información actualizada"]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Empresa inactivada"]);
    }
}
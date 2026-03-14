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
     * tags={"Configuración - Empresas"},
     * summary="Listar empresas activas",
     * description="Obtiene todas las empresas que se encuentran en estado activo (estado = 1), incluyendo el nombre de su plan.",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id_empresa", type="integer", example=1),
     * @OA\Property(property="nombre_empresa", type="string", example="Mi Empresa S.A.S"),
     * @OA\Property(property="numero_documento", type="string", example="900123456-7"),
     * @OA\Property(property="id_plan", type="integer", example=3),
     * @OA\Property(property="nombre_plan", type="string", example="Plan Premium"),
     * @OA\Property(property="estado", type="integer", example=1)
     * )
     * )
     * )
     * )
     */
    public function getAll() {
        $sql = "SELECT e.*, p.nombre_plan
                FROM empresas e
                LEFT JOIN planes p ON e.id_plan = p.id_plan
                WHERE e.estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(EmpresaSerializer::toList($data));
    }

    /**
     * @OA\Get(
     * path="/empresas/{id}",
     * operationId="getEmpresaById",
     * tags={"Configuración - Empresas"},
     * summary="Obtener una empresa por ID",
     * description="Devuelve los detalles de una única empresa especificada por su ID.",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de la empresa",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * @OA\Property(property="id_empresa", type="integer", example=1),
     * @OA\Property(property="nombre_empresa", type="string", example="Mi Empresa S.A.S"),
     * @OA\Property(property="numero_documento", type="string", example="900123456-7"),
     * @OA\Property(property="id_plan", type="integer", example=3),
     * @OA\Property(property="nombre_plan", type="string", example="Plan Premium"),
     * @OA\Property(property="estado", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=404, description="Empresa no encontrada")
     * )
     */
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
     * tags={"Configuración - Empresas"},
     * summary="Crear nueva empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_empresa", "numero_documento", "id_plan"},
     * @OA\Property(property="nombre_empresa", type="string", example="Nueva Empresa S.A.S"),
     * @OA\Property(property="numero_documento", type="string", example="901987654-3", description="También se acepta el campo 'nit'"),
     * @OA\Property(property="id_plan", type="integer", example=2)
     * )
     * ),
     * @OA\Response(response=201, description="Empresa registrada exitosamente"),
     * @OA\Response(response=400, description="Error de validación o documento duplicado")
     * )
     */
    public function create($input) {
        try {
            // Compatibilidad: si llega "nit", lo convertimos a "numero_documento"
            if (!isset($input['numero_documento']) && isset($input['nit'])) {
                $input['numero_documento'] = $input['nit'];
                unset($input['nit']);
            }

            if (empty($input['nombre_empresa']) || empty($input['numero_documento']) || empty($input['id_plan'])) {
                throw new Exception("Nombre, Número de documento e ID Plan son obligatorios.");
            }

            // Unicidad por número_documento
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE numero_documento = ?");
            $stmt->execute([$input['numero_documento']]);
            if ($stmt->fetchColumn() > 0) throw new Exception("El número de documento ya está registrado.");

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

    /**
     * @OA\Put(
     * path="/empresas/{id}",
     * operationId="updateEmpresa",
     * tags={"Configuración - Empresas"},
     * summary="Actualizar empresa existente",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de la empresa a actualizar",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_empresa", type="string", example="Empresa Actualizada S.A.S"),
     * @OA\Property(property="numero_documento", type="string", example="901987654-3", description="También se acepta 'nit'"),
     * @OA\Property(property="id_plan", type="integer", example=3)
     * )
     * ),
     * @OA\Response(response=200, description="Información actualizada"),
     * @OA\Response(response=400, description="Error de validación o documento duplicado")
     * )
     */
    public function update($id, $input) {
        try {
            // Compatibilidad: si llega "nit", lo convertimos a "numero_documento"
            if (!isset($input['numero_documento']) && isset($input['nit'])) {
                $input['numero_documento'] = $input['nit'];
                unset($input['nit']);
            }

            if (isset($input['numero_documento'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE numero_documento = ? AND id_empresa != ?");
                $stmt->execute([$input['numero_documento'], $id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("El número de documento ya pertenece a otra empresa.");
            }

            $success = $this->model->update($id, $input);
            return json_encode(["ok" => (bool)$success, "mensaje" => "Información actualizada"]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/empresas/{id}",
     * operationId="deleteEmpresa",
     * tags={"Configuración - Empresas"},
     * summary="Desactivar (eliminar lógicamente) una empresa",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de la empresa a inactivar",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Empresa inactivada exitosamente")
     * )
     */
    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => (bool)$success, "mensaje" => "Empresa inactivada"]);
    }
}
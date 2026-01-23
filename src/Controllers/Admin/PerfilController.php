<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PerfilModel;
use App\Serializers\Admin\PerfilSerializer; 
use Exception; 

class PerfilController extends GenericController {

    public function __construct($db) {
        parent::__construct($db, 'perfiles');
        $this->model = new PerfilModel($db);
    } 

    /**
     * @OA\Get(
     * path="/perfiles",
     * operationId="getPerfilesListCustom",
     * tags={"Seguridad - Perfiles"},
     * summary="Listar perfiles de seguridad",
     * @OA\Parameter(
     * name="id_empresa",
     * in="query",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Exitoso",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id_perfil", type="integer", example=1),
     * @OA\Property(property="nombre_perfil", type="string", example="Administrador"),
     * @OA\Property(property="descripcion", type="string", example="Acceso total"),
     * @OA\Property(property="estado", type="integer", example=1)
     * )
     * )
     * )
     * )
     */
    public function getAll() {
        $idEmpresa = $_GET['id_empresa'] ?? null;

        if ($idEmpresa) {
            $sql = "SELECT * FROM perfiles WHERE (id_empresa = ? OR id_empresa IS NULL) AND estado = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idEmpresa]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $data = $this->model->all();
        }

        return json_encode(PerfilSerializer::toList($data));
    }

    /** * @OA\Post(
     * path="/perfiles",
     * operationId="createPerfilCustom",
     * tags={"Seguridad - Perfiles"},
     * summary="Crear nuevo perfil",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_perfil"},
     * @OA\Property(property="nombre_perfil", type="string", example="Administrador"),
     * @OA\Property(property="descripcion", type="string", example="Acceso total al sistema"),
     * @OA\Property(property="id_empresa", type="integer", nullable=true)
     * )
     * ),
     * @OA\Response(response=201, description="Creado"),
     * @OA\Response(response=400, description="Error de validación")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['nombre_perfil'])) {
                throw new Exception("El nombre del perfil es obligatorio");
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Registrado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /** * @OA\Put(
     * path="/perfiles/{id}",
     * operationId="updatePerfilCustom",
     * tags={"Seguridad - Perfiles"},
     * summary="Actualizar perfil",
     * @OA\Parameter(
     * name="id", 
     * in="path", 
     * required=true, 
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_perfil", type="string"),
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
            $success = $this->model->update($id, $input);
            return json_encode(["ok" => $success, "mensaje" => "Actualizado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/perfiles/{id}",
     * operationId="deletePerfilCustom",
     * tags={"Seguridad - Perfiles"},
     * summary="Desactivar perfil",
     * @OA\Parameter(
     * name="id", 
     * in="path", 
     * required=true, 
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Desactivado")
     * )
     */
    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Desactivado correctamente"]);
    }
}
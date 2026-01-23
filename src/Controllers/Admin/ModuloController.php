<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\ModuloModel;
use App\Serializers\Admin\ModuloSerializer;
use Exception;

class ModuloController extends GenericController {
      
    public function __construct($db) {
        parent::__construct($db, 'modulos');
        $this->model = new ModuloModel($db); 
    }

    /**
     * @OA\Get(
     * path="/modulos",
     * operationId="getModulosList",
     * tags={"Admin - Catálogos"},
     * summary="Listar módulos y funciones",
     * @OA\Parameter(
     * name="todos",
     * in="query",
     * required=false,
     * @OA\Schema(type="string", enum={"true", "false"})
     * ),
     * @OA\Response(
     * response=200,
     * description="Exitoso",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Modulo"))
     * )
     * )
     */
    public function getAll() {
        $mostrarTodos = isset($_GET['todos']) && $_GET['todos'] === 'true';

        if ($mostrarTodos) {
            $data = $this->model->all();
        } else {
            $sql = "SELECT * FROM modulos WHERE estado = 1 ORDER BY id_padre ASC, id_modulo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return json_encode(ModuloSerializer::toList($data));
    }

    /**
     * @OA\Post(
     * path="/modulos",
     * operationId="createModulo",
     * tags={"Admin - Catálogos"},
     * summary="Crear módulo o función",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_modulo"},
     * @OA\Property(property="nombre_modulo", type="string", example="Inventarios"),
     * @OA\Property(property="descripcion", type="string", example="Control de stock"),
     * @OA\Property(property="icono", type="string", example="fas fa-box"),
     * @OA\Property(property="id_padre", type="integer", nullable=true, example=0)
     * )
     * ),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['nombre_modulo'])) throw new Exception("Nombre obligatorio");

            // Normalización: 0 o vacío se guarda como NULL (Módulo Principal)
            if (empty($input['id_padre']) || $input['id_padre'] == 0) {
                $input['id_padre'] = null;
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Registrado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/modulos/{id}",
     * operationId="updateModulo",
     * tags={"Admin - Catálogos"},
     * summary="Actualizar módulo",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_modulo", type="string"),
     * @OA\Property(property="id_padre", type="integer", nullable=true),
     * @OA\Property(property="estado", type="integer")
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        try {
            if (array_key_exists('id_padre', $input)) {
                if (empty($input['id_padre']) || $input['id_padre'] == 0) {
                    $input['id_padre'] = null;
                }
                if ($input['id_padre'] == $id) {
                    throw new Exception("Un módulo no puede ser su propio padre.");
                }
            }

            $success = $this->model->update($id, $input);
            return json_encode(["ok" => $success, "mensaje" => "Actualizado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => $success, "mensaje" => "Desactivado"]);
    }
}
<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\TipoempresaModel;
use App\Serializers\Admin\TipoEmpresaSerializer;
use Exception;

class TipoempresaController extends GenericController {
      
    public function __construct($db) {
        parent::__construct($db, 'tipo_empresa');
        $this->model = new TipoempresaModel($db); 
    }

    /**
     * @OA\Get(
     * path="/tipo-empresa",
     * operationId="getTipoEmpresaList",
     * tags={"Admin - Catálogos"},
     * summary="Listar tipos de empresa configurados",
     * @OA\Response(
     * response=200,
     * description="Exitoso",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/TipoEmpresa"))
     * )
     * )
     */
    public function getAll() {
        $mostrarTodos = isset($_GET['todos']) && $_GET['todos'] === 'true';

        if ($mostrarTodos) {
            $data = $this->model->all();
        } else {
            $sql = "SELECT * FROM tipo_empresa WHERE estado = 1 ORDER BY id_config ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return json_encode(TipoEmpresaSerializer::toList($data));
    }

    /**
     * @OA\Post(
     * path="/tipo-empresa",
     * operationId="createTipoEmpresa",
     * tags={"Admin - Catálogos"},
     * summary="Crear nueva configuración de tipo de empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"tamano_empresa", "sector", "empleados_desde", "empleados_hasta"},
     * @OA\Property(property="tamano_empresa", type="string", enum={"Micro", "Pequeña", "Mediana", "Grande"}),
     * @OA\Property(property="sector", type="string", description="Campo APLICACIÓN en formulario"),
     * @OA\Property(property="cantidad_sede", type="integer", description="Campo CANTIDAD POR SEDE"),
     * @OA\Property(property="empleados_desde", type="integer"),
     * @OA\Property(property="empleados_hasta", type="integer"),
     * @OA\Property(property="estado", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        try {
            // Validaciones según el formulario
            if (empty($input['tamano_empresa'])) throw new Exception("El tamaño de empresa es obligatorio");
            if (empty($input['sector'])) throw new Exception("El sector/aplicación es obligatorio");
            
            if (!isset($input['empleados_desde']) || !isset($input['empleados_hasta'])) {
                throw new Exception("Los rangos de empleados son obligatorios");
            }

            if ($input['empleados_desde'] > $input['empleados_hasta']) {
                throw new Exception("El valor 'Desde' no puede ser mayor al valor 'Hasta'");
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Configuración registrada correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function update($id, $input) {
        try {
            if (isset($input['empleados_desde']) && isset($input['empleados_hasta'])) {
                if ($input['empleados_desde'] > $input['empleados_hasta']) {
                    throw new Exception("Rango de empleados inválido.");
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
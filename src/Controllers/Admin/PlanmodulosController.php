<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PlanmodulosModel;
use App\Serializers\Admin\PlanmodulosSerializer;
use Exception;
use PDOException;

class PlanModulosController extends GenericController {

    protected $model;

    public function __construct($db) {
        // Importante: 'plan_modulos' es el nombre real de tu tabla en BD
        parent::__construct($db, 'plan_modulos');
        $this->model = new PlanmodulosModel($db);
    }

    /**
     * @OA\Get(
     * path="/planes/permisos/{id}",
     * operationId="getAccesosPlan",
     * tags={"Admin - Planes"},
     * summary="Listar matriz de accesos por plan",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Exitoso")
     * )
     */
    public function getPermisos($id) {
    // 1. Validar que llegue el ID
    if (!$id) {
        http_response_code(400);
        return json_encode(["status" => 400, "error" => "Falta el ID del plan"]);
    }

    // 2. Llamar al modelo
    $data = $this->model->getMatrix($id);

    // 3. Verificar si encontró algo
    if (empty($data)) {
        // Opcional: Devolver 200 con array vacío si es válido que no tenga permisos aún
        return json_encode([]); 
    }

    // 4. Devolver JSON
    http_response_code(200);
    return json_encode($data);
}

    /**
     * @OA\Post(
     * path="/planes/permisos/{id}",
     * operationId="saveAccesosPlan",
     * tags={"Admin - Planes"},
     * summary="Guardar matriz de accesos del plan (Sincronización completa)",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PlanAcceso"))),
     * @OA\Response(response=200, description="Accesos sincronizados") 
     * )
     */
    public function savePermisos($id, $input) {
        try {
            // 1. Iniciar Transacción (Todo o Nada)
            // Si falla algo, la BD vuelve al estado original.
            $this->db->beginTransaction();

            // 2. Limpiar los accesos actuales del plan
            // Asegúrate de tener este método en tu Modelo
            $this->model->clearModulos($id);

            // 3. Insertar los nuevos accesos uno por uno
            if (is_array($input) && count($input) > 0) {
                foreach ($input as $acceso) {
                    // Usamos 'create' porque así se llamaba en tu GenericModel original
                    $this->model->create([
                        'id_plan'   => $id,
                        // Validamos que venga id_modulo, si no, saltamos o falla
                        'id_modulo' => $acceso['id_modulo'], 
                        'ver'       => $acceso['ver'] ?? 0
                    ]);
                }
            }

            // 4. Confirmar cambios si todo salió bien
            $this->db->commit();

            return json_encode([
                'status' => 200, 
                'ok' => true, 
                'mensaje' => 'Accesos sincronizados correctamente'
            ]);

        } catch (PDOException $e) {
            // Error de Base de Datos
            $this->db->rollBack();
            http_response_code(500);
            return json_encode([
                'error_critico' => 'Error SQL al guardar permisos',
                'detalle' => $e->getMessage()
            ]);

        } catch (Exception $e) {
            // Error Genérico
            $this->db->rollBack();
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Sobrescritura para compatibilidad con el router genérico
     */
    public function update($id, $input = null) {
        // Redirigimos la petición PUT/PATCH a nuestra lógica personalizada
        return $this->savePermisos($id, $input);
    }
}
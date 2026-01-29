<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PermisoModel;
use App\Serializers\Admin\PermisoSerializer;
use Exception;

class PermisoController extends GenericController {

    protected $model;

    public function __construct($db) {
        parent::__construct($db, 'perfil_permisos');
        $this->model = new PermisoModel($db);
    }

    /**
     * @OA\Get(
     * path="/perfiles/permisos/{id}",
     * operationId="getPermisosPerfil",
     * tags={"Seguridad - Permisos"},
     * summary="Listar matriz de permisos",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Exitoso")
     * )
     */
    public function getPermisos($id) {
    if (!$id) {
        http_response_code(400);
        return json_encode(['error' => 'ID requerido']);
    }

    $data = $this->model->getMatrix($id);

    // DEBUG: Si esto sale en pantalla en el Swagger, sabremos qué hay en la BD
    // var_dump($data); die(); 

    return json_encode(PermisoSerializer::toList($data));
}
    /**
     * @OA\Post(
     * path="/perfiles/permisos/{id}",
     * operationId="savePermisosPerfil",
     * tags={"Seguridad - Permisos"},
     * summary="Guardar matriz de permisos",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Permiso"))),
     * @OA\Response(response=200, description="Permisos guardados") 
     * )
     */
    public function savePermisos($id, $input) {
        try {
            $this->model->clearPermisos($id);
            foreach ($input as $permiso) {
                // Sincronizado con nombres de columnas reales
                $this->model->insert([
                    'id_perfil'    => $id,
                    'id_modulo'    => $permiso['id_modulo'],
                    'can_ver'      => $permiso['ver'] ?? 0,
                    'can_crear'    => $permiso['crear'] ?? 0,
                    'can_editar'   => $permiso['editar'] ?? 0,
                    'can_eliminar' => $permiso['eliminar'] ?? 0
                ]);
            }
            return json_encode(['status' => 200, 'mensaje' => 'Actualizado']);
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    // Firma corregida para eliminar error P1038 de Intelephense
    public function update($id, $input = null) {
        return $this->savePermisos($id, $input);
    }
}
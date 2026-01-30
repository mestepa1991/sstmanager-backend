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
                $this->model->create([
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
   /**
     * @OA\Get(
     * path="/perfiles/permisos/{id}/check-all",
     * operationId="getAllAccess",
     * tags={"Seguridad - Permisos"},
     * summary="Obtener todos los permisos formateados para validación inicial",
     * @OA\Parameter(name="id", in="path", description="ID del Perfil", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Matriz de permisos optimizada")
     * )
     */
    public function getAllAccess($id) {
        if (!$id) {
            http_response_code(400);
            return json_encode(['error' => 'ID de perfil requerido']);
        }

        $data = $this->model->getMatrix($id);
        $permissionsMap = [];

        foreach ($data as $p) {
            $item = (array)$p;
            $idModulo = $item['id_modulo'];

            // Creamos un mapa indexado por ID de módulo
            $permissionsMap[$idModulo] = [
                'ver'      => (isset($item['can_ver']) && $item['can_ver'] == 1) || (isset($item['ver']) && $item['ver'] == 1),
                'crear'    => (isset($item['can_crear']) && $item['can_crear'] == 1) || (isset($item['crear']) && $item['crear'] == 1),
                'editar'   => (isset($item['can_editar']) && $item['can_editar'] == 1) || (isset($item['editar']) && $item['editar'] == 1),
                'eliminar' => (isset($item['can_eliminar']) && $item['can_eliminar'] == 1) || (isset($item['eliminar']) && $item['eliminar'] == 1)
            ];
        }

        return json_encode([
            'perfil_id' => (int)$id,
            'modulos'   => $permissionsMap
        ]);
    }
}
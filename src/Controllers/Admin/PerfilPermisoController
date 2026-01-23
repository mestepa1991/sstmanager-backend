<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PermisoModel;
use App\Serializers\Admin\PermisoSerializer;
use Exception;

class PerfilPermisoController extends GenericController {
    
    public function __construct($db) {
        parent::__construct($db, 'perfil_permisos');
        $this->model = new PermisoModel($db);
    }

    /**
     * @OA\Get(
     * path="/perfiles/{id}/permisos",
     * operationId="getPerfilPermisosCustom",
     * tags={"Seguridad - Permisos"},
     * summary="Obtener matriz de permisos de un perfil",
     * description="Retorna una lista de módulos con sus respectivos permisos para el ID de perfil proporcionado.",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del perfil para consultar permisos",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200, 
     * description="Matriz de permisos obtenida exitosamente",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id_modulo", type="integer"),
     * @OA\Property(property="nombre_modulo", type="string"),
     * @OA\Property(property="ver", type="integer"),
     * @OA\Property(property="crear", type="integer"),
     * @OA\Property(property="editar", type="integer"),
     * @OA\Property(property="eliminar", type="integer")
     * )
     * )
     * ),
     * @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getPermisos($id) {
        try {
            $sql = "SELECT p.*, m.nombre_modulo 
                    FROM perfil_permisos p 
                    INNER JOIN modulos m ON p.id_modulo = m.id_modulo 
                    WHERE p.id_perfil = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return json_encode(PermisoSerializer::toList($data));

        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     * path="/perfiles/{id}/permisos",
     * operationId="savePerfilPermisosCustom",
     * tags={"Seguridad - Permisos"},
     * summary="Sincronizar permisos del perfil",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(
     * property="permisos", 
     * type="array", 
     * @OA\Items(
     * @OA\Property(property="id_modulo", type="integer", example=1),
     * @OA\Property(property="ver", type="integer", example=1),
     * @OA\Property(property="crear", type="integer", example=0),
     * @OA\Property(property="editar", type="integer", example=1),
     * @OA\Property(property="eliminar", type="integer", example=0)
     * )
     * )
     * )
     * ),
     * @OA\Response(response=200, description="Permisos actualizados correctamente"),
     * @OA\Response(response=400, description="Error en la validación o transacción")
     * )
     */
    public function savePermisos($id, $input = null) {
        $data = $input['permisos'] ?? [];
        
        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare("DELETE FROM perfil_permisos WHERE id_perfil = ?");
            $delete->execute([$id]);

            $sql = "INSERT INTO perfil_permisos (id_perfil, id_modulo, can_ver, can_crear, can_editar, can_eliminar) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($data as $p) {
                $stmt->execute([
                    $id, 
                    $p['id_modulo'], 
                    (int)($p['ver'] ?? 0), 
                    (int)($p['crear'] ?? 0), 
                    (int)($p['editar'] ?? 0), 
                    (int)($p['eliminar'] ?? 0)
                ]);
            }

            $this->db->commit();
            return json_encode(["ok" => true, "mensaje" => "Permisos actualizados correctamente"]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            return json_encode(["error" => "Error al sincronizar: " . $e->getMessage()]);
        }
    }
}
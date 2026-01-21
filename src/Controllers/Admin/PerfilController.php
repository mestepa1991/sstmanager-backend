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
     * path="/index.php?table=perfiles",
     * tags={"Admin"},
     * summary="Listar perfiles (Filtro Multi-empresa)",
     * description="Trae los perfiles de la empresa actual + los perfiles Master globales.",
     * @OA\Parameter(name="id_empresa", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Lista filtrada")
     * )
     */
    public function getAll() {
        // Obtenemos el id_empresa (en el futuro vendrá de la sesión/token)
        $idEmpresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;

        // Lógica: Mis perfiles O los perfiles globales (IS NULL)
        $sql = "SELECT * FROM perfiles 
                WHERE (id_empresa = :id_e OR id_empresa IS NULL) 
                AND estado = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_e' => $idEmpresa]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(PerfilSerializer::toList($data));
    }

    /**
     * @OA\Get(
     * path="/index.php?table=perfiles&action=matriz",
     * tags={"Admin"},
     * summary="Obtener matriz global de permisos filtrada",
     * @OA\Parameter(name="id_empresa", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Matriz de seguridad")
     * )
     */
    public function getMatrizPermisos() {
        $idEmpresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;

        // 1. Obtener perfiles filtrados por empresa
        $sqlPerfiles = "SELECT * FROM perfiles WHERE (id_empresa = :id_e OR id_empresa IS NULL) AND estado = 1";
        $stmtP = $this->db->prepare($sqlPerfiles);
        $stmtP->execute([':id_e' => $idEmpresa]);
        $perfiles = $stmtP->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Obtener permisos
        $sqlPermisos = "SELECT p.*, m.nombre_modulo 
                        FROM perfil_permisos p 
                        INNER JOIN modulos m ON p.id_modulo = m.id_modulo";
        $todosLosPermisos = $this->db->query($sqlPermisos)->fetchAll(\PDO::FETCH_ASSOC);

        $permisosAgrupados = [];
        foreach ($todosLosPermisos as $p) {
            $permisosAgrupados[$p['id_perfil']][] = $p;
        }

        $resultado = [];
        foreach ($perfiles as $perfil) {
            $misPermisos = $permisosAgrupados[$perfil['id_perfil']] ?? [];
            $resultado[] = PerfilSerializer::toArray($perfil, $misPermisos);
        }

        return json_encode($resultado);
    }

    /**
     * @OA\Post(
     * path="/index.php?table=perfiles",
     * tags={"Admin"},
     * summary="Crear perfil asignado a empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_perfil", type="string", example="Supervisor Local"),
     * @OA\Property(property="id_empresa", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        $this->db->beginTransaction();
        try {
            // Validar que el nombre no exista ya para ESA empresa
            $idEmpresa = $input['id_empresa'] ?? null;
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM perfiles WHERE nombre_perfil = ? AND id_empresa <=> ?");
            $stmt->execute([$input['nombre_perfil'], $idEmpresa]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Ya existe un perfil con ese nombre en esta empresa.");
            }

            // 1. Crear el perfil
            $idPerfil = $this->model->create($input);

            // 2. Si vienen permisos en el mismo JSON de creación, guardarlos (Reutilizamos lógica)
            if (isset($input['permisos']) && is_array($input['permisos'])) {
                $this->savePermisosBatch($idPerfil, $input['permisos']);
            }

            $this->db->commit();
            return json_encode(["id" => $idPerfil, "mensaje" => "Perfil creado con éxito"]);
        } catch (Exception $e) {
            $this->db->rollBack();
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    // Helper para no repetir código de guardado de permisos
    private function savePermisosBatch($idPerfil, $permisos) {
        $sql = "INSERT INTO perfil_permisos (id_perfil, id_modulo, can_ver, can_crear, can_editar, can_eliminar) 
                VALUES (:id_p, :id_m, :v, :c, :e, :d)
                ON DUPLICATE KEY UPDATE 
                can_ver=VALUES(can_ver), can_crear=VALUES(can_crear), 
                can_editar=VALUES(can_editar), can_eliminar=VALUES(can_eliminar)";
        
        $stmt = $this->db->prepare($sql);
        foreach ($permisos as $p) {
            $stmt->execute([
                ':id_p' => $idPerfil,
                ':id_m' => $p['id_modulo'],
                ':v' => (int)($p['ver'] ?? 0),
                ':c' => (int)($p['crear'] ?? 0),
                ':e' => (int)($p['editar'] ?? 0),
                ':d' => (int)($p['eliminar'] ?? 0)
            ]);
        }
    }
    /**
     * @OA\Put(
     * path="/index.php?table=perfiles&id={id}",
     * tags={"Admin"},
     * summary="Actualizar perfil y su matriz de permisos",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_perfil", type="string", example="Supervisor SST"),
     * @OA\Property(property="descripcion", type="string"),
     * @OA\Property(
     * property="permisos", 
     * type="array", 
     * @OA\Items(
     * @OA\Property(property="id_modulo", type="integer"),
     * @OA\Property(property="ver", type="boolean"),
     * @OA\Property(property="crear", type="boolean"),
     * @OA\Property(property="editar", type="boolean"),
     * @OA\Property(property="eliminar", type="boolean")
     * )
     * )
     * )
     * ),
     * @OA\Response(response=200, description="Perfil actualizado exitosamente")
     * )
     */
    public function update($id, $input) {
        $this->db->beginTransaction();
        try {
            // 1. Actualizar datos básicos del perfil
            $datosBasicos = [];
            if (isset($input['nombre_perfil'])) $datosBasicos['nombre_perfil'] = $input['nombre_perfil'];
            if (isset($input['descripcion']))   $datosBasicos['descripcion']   = $input['descripcion'];
            if (isset($input['estado']))        $datosBasicos['estado']        = $input['estado'];
            if (isset($input['id_empresa']))    $datosBasicos['id_empresa']    = $input['id_empresa'];

            if (!empty($datosBasicos)) {
                $this->model->update($id, $datosBasicos);
            }

            // 2. Actualizar permisos si vienen en el request
            if (isset($input['permisos']) && is_array($input['permisos'])) {
                $this->savePermisosBatch($id, $input['permisos']);
            }

            $this->db->commit();
            return json_encode(["ok" => true, "mensaje" => "Perfil y matriz de permisos actualizados"]);

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return json_encode(["ok" => false, "error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=perfiles&id={id}",
     * tags={"Admin"},
     * summary="Desactivar perfil (Borrado Lógico)",
     * description="Cambia el estado del perfil a 0 para mantener la integridad histórica.",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Perfil desactivado")
     * )
     */
    public function delete($id) {
        try {
            // Verificamos que no sea el perfil Master global (opcional por seguridad)
            $perfil = $this->model->find($id);
            if ($perfil && $perfil['nombre_perfil'] === 'Master' && $perfil['id_empresa'] === null) {
                throw new Exception("No se puede desactivar el perfil Master del sistema.");
            }

            $success = $this->model->update($id, ['estado' => 0]);
            
            return json_encode([
                "ok" => $success, 
                "mensaje" => $success ? "Perfil desactivado correctamente" : "No se pudo desactivar el perfil"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["ok" => false, "error" => $e->getMessage()]);
        }
    }
}
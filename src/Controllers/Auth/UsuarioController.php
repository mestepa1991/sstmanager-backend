<?php
namespace App\Controllers\Auth;

use OpenApi\Annotations as OA;

use App\Controllers\GenericController;
use App\Models\Auth\UsuarioModel;
use App\Serializers\Auth\UsuarioSerializer;
use Exception;

class UsuarioController extends GenericController {
  
    public function __construct($db) {
        parent::__construct($db, 'usuarios');
        $this->model = new UsuarioModel($db); 
    }

    /**
     * @OA\Get(
     * path="/index.php?table=usuarios",
     * tags={"Usuarios"},
     * summary="Listar usuarios con filtros de empresa",
     * @OA\Parameter(name="id_empresa", in="query", description="Filtrar por empresa (opcional para Master)", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Lista de usuarios", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Usuario")))
     * )
     */
    public function getAll() {
        $idEmpresa = $_GET['id_empresa'] ?? null;

        // JOIN para traer nombres de perfiles y empresas en una sola consulta
        $sql = "SELECT u.*, p.nombre_perfil, e.nombre_empresa 
                FROM usuarios u 
                INNER JOIN perfiles p ON u.id_perfil = p.id_perfil 
                LEFT JOIN empresas e ON u.id_empresa = e.id_empresa";
        
        $params = [];
        if ($idEmpresa !== null) {
            $sql .= " WHERE u.id_empresa = :id_e AND u.estado = 1";
            $params[':id_e'] = $idEmpresa;
        } else {
            $sql .= " WHERE u.estado = 1";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(UsuarioSerializer::toList($data));
    }

    /**
     * @OA\Get(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Usuarios"},
     * summary="Obtener un usuario por ID",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos del usuario")
     * )
     */
    public function getOne($id) {
        $sql = "SELECT u.*, p.nombre_perfil, e.nombre_empresa 
                FROM usuarios u 
                INNER JOIN perfiles p ON u.id_perfil = p.id_perfil 
                LEFT JOIN empresas e ON u.id_empresa = e.id_empresa
                WHERE u.id_usuario = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            return json_encode(["error" => "Usuario no encontrado"]);
        }

        return json_encode(UsuarioSerializer::toArray($user));
    }

    /**
     * @OA\Post(
     * path="/index.php?table=usuarios",
     * tags={"Usuarios"},
     * summary="Crear usuario",
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Usuario")),
     * @OA\Response(response=201, description="Creado")
     * )
     */
    public function create($input) {
        try {
            // Validaciones básicas
            if (empty($input['numero_documento']) || empty($input['password'])) {
                throw new Exception("Documento y password son obligatorios.");
            }

            // Lógica de Roles vs Empresa
            if (in_array($input['rol'], ['master', 'soporte'])) {
                $input['id_empresa'] = null;
            } elseif (empty($input['id_empresa'])) {
                throw new Exception("Usuarios de cliente deben tener una empresa asignada.");
            }

            // Encriptar password
            $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Usuario registrado correctamente"]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

      /**
     * @OA\Put(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Usuarios"},
     * summary="Reemplazar usuario (Actualización Completa)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/Usuario")
     * ),
     * @OA\Response(response=200, description="Usuario reemplazado correctamente")
     * )
     */
    public function update($id, $input) {
        try {
            if (empty($input)) throw new Exception("No se enviaron datos para actualizar.");

            // Si se intenta cambiar la contraseña, se vuelve a encriptar
            if (!empty($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $success = $this->model->update($id, $input);
            
            return json_encode([
                "ok" => $success,
                "mensaje" => $success ? "Registro actualizado integralmente" : "No se realizaron cambios"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Patch(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Usuarios"},
     * summary="Actualización Parcial (Solo campos enviados)",
     * description="Envía solo los campos que deseas modificar, por ejemplo: {'estado': 0} o {'id_perfil': 2}",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="id_perfil", type="integer"),
     * @OA\Property(property="estado", type="integer")
     * )
     * ),
     * @OA\Response(response=200, description="Campo actualizado")
     * )
     */
    public function patch($id, $input) {
        // En tu GenericModel, el método update ya es flexible y solo actualiza los campos presentes en el array.
        // Por lo tanto, patch y update pueden compartir la lógica interna, pero Swagger los muestra distinto.
        return $this->update($id, $input);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Usuarios"},
     * summary="Inactivar Usuario",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado a inactivo")
     * )
     */
    public function delete($id) {
        // Implementamos trazabilidad: No borramos la fila, cambiamos el estado
        $success = $this->model->update($id, ['estado' => 0]);
        
        return json_encode([
            "ok" => $success,
            "mensaje" => $success ? "El usuario ha sido inactivado para mantener trazabilidad" : "Error al procesar"
        ]);
    }
}
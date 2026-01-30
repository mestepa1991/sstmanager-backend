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
     * tags={"Auth"},
     * summary="Listar usuarios con filtros de empresa",
     * @OA\Parameter(name="id_empresa", in="query", description="Filtrar por empresa (opcional para Master)", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Lista de usuarios", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Usuario")))
     * )
     */
    public function getAll() {
        $idEmpresa = $_GET['id_empresa'] ?? null;

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
     * tags={"Auth"},
     * summary="Obtener un usuario por ID",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos del usuario", @OA\JsonContent(ref="#/components/schemas/Usuario"))
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
     * tags={"Auth"},
     * summary="Crear usuario",
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Usuario")),
     * @OA\Response(response=201, description="Usuario creado con éxito")
     * )
     */
   public function create($input) {      
        try {
            // --- LIMPIEZA DE DATOS ---
            unset($input['id']);
            unset($input['id_usuario']); 

            if (empty($input['email']) || empty($input['password']) || empty($input['numero_documento'])) {
                throw new Exception("Email, Documento y password son obligatorios.");
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del correo electrónico no es válido.");
            }

            // Encriptación
            $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);

            // --- LÓGICA DE ROL APLICADA A EMPRESA Y PERFIL ---
            if (in_array($input['rol'], ['master', 'soporte'])) {
                // Usuarios de alto nivel no pertenecen a una empresa específica
                $input['id_empresa'] = null; 
                
                // Si para estos roles el perfil es opcional o fijo, lo forzamos a null 
                // o a un ID específico si tu base de datos lo requiere.
                // Aquí lo ponemos null siguiendo la lógica de la empresa.
                $input['id_perfil'] = !empty($input['id_perfil']) ? $input['id_perfil'] : null;

            } else {
                // Usuarios normales DEBEN tener empresa y perfil
                if (empty($input['id_empresa'])) {
                    throw new Exception("Usuarios de cliente deben tener una empresa asignada.");
                }
                if (empty($input['id_perfil'])) {
                    throw new Exception("Usuarios de cliente deben tener un perfil de accesos asignado.");
                }
            }

            // 3. Intento de Creación
            $id = $this->model->create($input);

            if (!$id) {
                throw new Exception("Error al insertar en BD. Verifique que el correo o documento no estén duplicados.");
            }

            return json_encode([
                "ok" => true,
                "id" => $id, 
                "mensaje" => "Usuario registrado correctamente con el email: " . $input['email']
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    } 
    /**
     * @OA\Put(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Auth"},
     * summary="Reemplazar usuario (Actualización Completa)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Usuario")),
     * @OA\Response(response=200, description="Usuario actualizado correctamente")
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
     * tags={"Auth"},
     * summary="Actualización Parcial (Solo campos enviados)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="email", type="string", example="nuevo@correo.com"),
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="id_perfil", type="integer"),
     * @OA\Property(property="estado", type="integer")
     * )
     * ),
     * @OA\Response(response=200, description="Campo actualizado")
     * )
     */
    public function patch($id, $input) {
        return $this->update($id, $input);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=usuarios&id={id}",
     * tags={"Auth"},
     * summary="Inactivar Usuario",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado a inactivo")
     * )
     */
    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        
        return json_encode([
            "ok" => $success,
            "mensaje" => $success ? "El usuario ha sido inactivado para mantener trazabilidad" : "Error al procesar"
        ]);
    }
}
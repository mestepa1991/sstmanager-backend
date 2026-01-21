<?php
namespace App\Controllers\Auth;

use App\Models\Auth\UsuarioModel;
use App\Serializers\Auth\UsuarioSerializer; // Usamos el serializer para devolver datos limpios
use Firebase\JWT\JWT; 
use Exception;

/**
 * @OA\Tag(name="Auth", description="Gestión de Tokens y Acceso")
 */
class AuthController {
    private $db;
    private $usuarioModel;
    private $secret_key = "SST_MANAGER_SECURE_KEY_2026_COLOMBIA_VERSION_ULTRA_SECRETA"; 

    public function __construct($db) {
        $this->db = $db;
        $this->usuarioModel = new UsuarioModel($db);
    }

    /**
     * @OA\Post(
     * path="/index.php?action=login",
     * tags={"Auth"},
     * summary="Obtener Token JWT",
     * description="Envía email y contraseña para recibir un token de acceso.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", example="admin@sst.com"),
     * @OA\Property(property="password", type="string", example="123456")
     * )
     * ),
     * @OA\Response(
     * response=200, 
     * description="Token generado",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="token", type="string", example="eyJ0eXAiOiJK..."),
     * @OA\Property(property="user", ref="#/components/schemas/Usuario")
     * )
     * ),
     * @OA\Response(response=401, description="Credenciales inválidas")
     * )
     */
    public function login($input) {
        try {
            // 1. Validar entrada
            if (empty($input['email']) || empty($input['password'])) {
                http_response_code(400);
                return json_encode(["status" => "error", "message" => "Email y contraseña requeridos"]);
            }

            // 2. Validar credenciales (Usando tu modelo existente)
            $usuario = $this->usuarioModel->validarUsuario($input['email'], $input['password']);

            if ($usuario) {
                // 3. Crear Payload del Token
                $issuedAt = time();
                $expirationTime = $issuedAt + (60 * 60 * 8); // 8 horas
                
                $payload = [
                    "iss" => "SST_SYSTEM",       // Emisor
                    "iat" => $issuedAt,          // Emitido en
                    "exp" => $expirationTime,    // Expira en
                    "data" => [                  // Datos incrustados
                        "id" => $usuario['id_usuario'],
                        "rol" => $usuario['rol'],
                        "empresa" => $usuario['id_empresa']
                    ]
                ];

                // 4. Generar JWT
                $jwt = JWT::encode($payload, $this->secret_key, 'HS256');

                return json_encode([
                    "status" => "success",
                    "mensaje" => "Autenticación exitosa",
                    "token" => $jwt,
                    "user" => UsuarioSerializer::toArray($usuario) // Datos limpios del usuario
                ]);
            }

            // 5. Error de credenciales
            http_response_code(401);
            return json_encode(["status" => "error", "message" => "Credenciales incorrectas"]);

        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(["status" => "error", "message" => "Error interno: " . $e->getMessage()]);
        }
    }
}
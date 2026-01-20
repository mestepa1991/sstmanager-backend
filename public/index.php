<?php
use OpenApi\Annotations as OA;
use App\Config\Database;
use App\Controllers\GenericController;
use App\Controllers\UsuarioController; // Importamos el nuevo controlador

/**
 * @OA\Info(
 * title="SSTManager API",
 * version="1.0.0",
 * description="Sistema de Gestión de Seguridad y Salud en el Trabajo"
 * )
 * @OA\Server(
 * url="/sstmanager-backend/public",
 * description="Servidor Local"
 * )
 */

/**
 * @OA\Schema(
 * schema="GenericBody",
 * title="Cuerpo de Petición",
 * @OA\Property(property="nombre", type="string", example="Ejemplo"),
 * @OA\Property(property="descripcion", type="string", example="Detalle del registro")
 * )
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cabeceras de respuesta y CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$table  = $_GET['table'] ?? null;
$id     = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = json_decode(file_get_contents("php://input"), true);

$tablasPermitidas = [
    'ciclos_phva', 'estandares_tipos', 'formularios', 'formulario_items', 
    'frecuencias', 'items_estandar', 'modulos', 'perfiles', 'perfil_permisos', 
    'planes', 'plan_modulos_permisos', 'rangos_calificacion', 'recursos', 
    'responsables', 'tipos_norma', 'usuarios'
];

if (!$table) {
    echo json_encode(["sistema" => "SSTManager API", "mensaje" => "Usa ?table=nombre_tabla"]);
    exit;
}

if (!in_array($table, $tablasPermitidas)) {
    http_response_code(404);
    echo json_encode(["error" => "Tabla '$table' no permitida"]);
    exit;
}

/**
 * ENRUTAMIENTO LÓGICO
 * Si la tabla es 'usuarios', usamos el controlador especializado.
 * Para cualquier otra tabla, usamos el controlador genérico.
 */
if ($table === 'usuarios') {
    $controller = new UsuarioController($db);
    
    // Ejecutamos los métodos específicos que creamos en UsuarioController
    switch ($method) {
        case 'GET':
            echo $id ? $controller->getUser($id) : $controller->getAllUsers();
            break;
        case 'POST':
            echo $controller->create($input);
            break;
        case 'PUT':
            // Asumiendo que implementaremos update en el controlador
            echo $controller->update($id, $input);
            break;
        case 'DELETE':
            echo $controller->delete($id);
            break;
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no soportado"]);
            break;
    }
} else {
    // Uso del controlador genérico para las otras 15 tablas
    $controller = new GenericController($db, $table);
    echo $controller->handleRequest($method, $id, $input);
}
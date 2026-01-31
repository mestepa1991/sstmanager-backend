<?php
use App\Config\Database;
use App\Controllers\Auth\AuthController; 
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\PerfilController;
use App\Controllers\Admin\PermisoController; 
use App\Controllers\Admin\PlanModulosController;
use App\Controllers\Admin\TipoempresaController; 

require_once __DIR__ . '/../vendor/autoload.php';

// 1. CONFIGURACIÓN DE CABECERAS Y CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. CONEXIÓN A LA BASE DE DATOS
$database = new Database(); 
$db = $database->getConnection();

// 3. CAPTURA DE RUTA
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptPath = dirname($_SERVER['SCRIPT_NAME']); 
$requestPath = str_replace($scriptPath, '', $uri); 
$pathSegments = explode('/', trim($requestPath, '/')); 
 
$table  = $_GET['table']  ?? ($pathSegments[0] ?? null);
$action = $_GET['action'] ?? ($pathSegments[1] ?? null);
$id     = $_GET['id']     ?? ($pathSegments[2] ?? null);
 
if (is_numeric($action)) {
    $id = $action;
    $action = null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = json_decode(file_get_contents("php://input"), true) ?? [];

// 4. ENRUTAMIENTO
try {

    // --- LOGIN ---
    if ($table === 'login' || ($action === 'login' && $method === 'POST')) {
        $authController = new AuthController($db); 
        echo $authController->login($input);       
        exit;
    }

    if (!$table) {
        echo json_encode(["status" => 200, "info" => "API SST-MANAGER En línea"]);
        exit;
    }

    // =======================================================
    // BLOQUE 1: PERFILES
    // =======================================================
    if ($table === 'perfiles' && $action === 'permisos') { 
        $controller = new PermisoController($db);
        echo match ($method) {
            'GET'         => $controller->getPermisos($id),
            'POST', 'PUT' => $controller->savePermisos($id, $input),
            'DELETE'      => $controller->delete($id),
            default       => throw new Exception("Método no permitido", 405)
        };
        exit;
    }

    if ($table === 'perfiles') { 
        $controller = new PerfilController($db);
        echo match ($method) {
            'GET'    => $id ? $controller->getAll($id) : $controller->getAll(), 
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no soportado", 405)
        };
        exit;
    }

    // =======================================================
    // BLOQUE 2: PLANES Y MATRIZ
    // =======================================================
    if ($table === 'planes' && $action === 'permisos') {
        $controller = new PlanModulosController($db);
        echo match ($method) {
            'GET'         => $controller->getPermisos($id),
            'POST', 'PUT' => $controller->savePermisos($id, $input),
            default       => throw new Exception("Método no permitido para permisos de planes", 405)
        };
        exit;
    }

    // =======================================================
    // BLOQUE 3: TIPO DE EMPRESA (Configuración Basada en Formulario)
    // =======================================================
    if ($table === 'tipo-empresa') {
        $controller = new TipoempresaController($db);
        echo match ($method) {
            'GET'    => $controller->getAll(), // Usa el Serializer para dar formato al Switch y Rangos
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no soportado para tipo-empresa", 405)
        };
        exit;
    }

    // =======================================================
    // BLOQUE 4: USUARIOS
    // =======================================================
    if ($table === 'usuarios') {
        $controller = new UsuarioController($db); 
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no soportado", 405)
        };
        exit;
    }

    // --- FALLBACK GENÉRICO ---
    $controller = new App\Controllers\GenericController($db, $table);
    echo $controller->handleRequest($method, $id, $input);

} catch (Exception $e) {
    $exCode = $e->getCode();
    $httpCode = (is_int($exCode) && $exCode >= 100 && $exCode <= 599) ? $exCode : 500;

    http_response_code($httpCode);
    echo json_encode([
        "status" => $httpCode,
        "error"  => $e->getMessage(),
        "debug_code" => $exCode
    ]);
}
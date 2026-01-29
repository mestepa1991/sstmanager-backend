<?php
use App\Config\Database;
use App\Controllers\Auth\AuthController; 
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\PerfilController;
use App\Controllers\Admin\PermisoController; 
use App\Controllers\Admin\CicloController;
use App\Controllers\Admin\CalificacionController;

require_once __DIR__ . '/../vendor/autoload.php';

// 1. CONFIGURACIÓN DE CABECERAS Y CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de peticiones preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. CONEXIÓN A LA BASE DE DATOS
$database = new Database(); 
$db = $database->getConnection();

/**
 * 3. CAPTURA INTELIGENTE DE RUTA
 * Soluciona el error "INSERT INTO public" detectado en Swagger.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($uri, '/'));

/**
 * Ajuste de índices para XAMPP (localhost/sstmanager-backend/public/tabla/accion/id):
 * [0] => sstmanager-backend, [1] => public, [2] => tabla, [3] => accion o id
 */
$table  = $_GET['table']  ?? ($uriSegments[2] ?? null);
$action = $_GET['action'] ?? ($uriSegments[3] ?? null);
$id     = $_GET['id']     ?? ($uriSegments[4] ?? null);

// Si el segmento detectado como 'action' es un número, realmente es un ID
if (is_numeric($action)) {
    $id = $action;
    $action = null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = json_decode(file_get_contents("php://input"), true) ?? [];

// 4. ENRUTAMIENTO PRINCIPAL
try {
    // --- CASO ESPECIAL: LOGIN ---
    if ($table === 'login' || ($action === 'login' && $method === 'POST')) {
        $authController = new AuthController($db); 
        echo $authController->login($input);       
        exit;
    }

    // Validación: Si no hay tabla, devolvemos estado base
    if (!$table) {
        echo json_encode([
            "status" => 200,
            "sistema" => "SST-MANAGER API", 
            "estado" => "En línea"
        ]);
        exit;
    }

    // --- CASO 1: PERMISOS (Independiente) ---
    // Detecta: /perfiles/permisos/{id}
    if ($table === 'perfiles' && $action === 'permisos') {
        $controller = new PermisoController($db);
        echo match ($method) {
            'GET'         => $controller->getPermisos($id),
            'POST', 'PUT' => $controller->savePermisos($id, $input),
            'DELETE'      => $controller->delete($id),
            default       => throw new Exception("Método $method no permitido para permisos", 405)
        };
        exit;
    }

    // --- CASO 2: PERFILES (CRUD Estándar) ---
    if ($table === 'perfiles') {
        $controller = new PerfilController($db);
        echo match ($method) {
            'GET'    => $id ? $controller->getAll($id) : $controller->getAll(), 
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método $method no soportado", 405)
        };
        exit;
    }

    // --- CASO 3: USUARIOS ---
    if ($table === 'usuarios') {
        $controller = new UsuarioController($db); 
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método $method no soportado", 405)
        };
        exit;
    }

    // --- FALLBACK GENÉRICO ---
    $controller = new App\Controllers\GenericController($db, $table);
    echo $controller->handleRequest($method, $id, $input);

} catch (Exception $e) {
    // Manejo de errores global con códigos HTTP
    $code = ($e->getCode() >= 100 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode([
        "status" => $code,
        "error"  => $e->getMessage()
    ]);
}
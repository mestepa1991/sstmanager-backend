<?php

use App\Config\Database;
use App\Controllers\Auth\AuthController;
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\PerfilController;
use App\Controllers\Admin\PermisoController;
use App\Controllers\Admin\PlanModulosController;
use App\Controllers\Admin\TipoempresaController;
use App\Controllers\Admin\CalificacionController;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * =========================================================
 * 1) CORS + HEADERS (ANTES DE CUALQUIER OUTPUT)
 * =========================================================
 */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


/**
 * =========================================================
 * 2) CONEXIÓN A BASE DE DATOS
 * =========================================================
 */
$database = new Database();
$db = $database->getConnection();

/**
 * =========================================================
 * 3) CAPTURA DE RUTA / PARÁMETROS
 * =========================================================
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$requestPath = str_replace($scriptPath, '', $uri);
$pathSegments = explode('/', trim($requestPath, '/'));

$table  = $_GET['table']  ?? ($pathSegments[0] ?? null);
$action = $_GET['action'] ?? ($pathSegments[1] ?? null);
$id     = $_GET['id']     ?? ($pathSegments[2] ?? null);

// Soporte /tabla/123
if (is_numeric($action)) {
    $id = $action;
    $action = null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = json_decode(file_get_contents("php://input"), true) ?? [];

/**
 * =========================================================
 * 4) ENRUTAMIENTO
 * =========================================================
 */
try {

    // ---------- LOGIN ----------
    if ($table === 'login' || ($action === 'login' && $method === 'POST')) {
        $authController = new AuthController($db);
        echo $authController->login($input);
        exit;
    }

    if (!$table) {
        echo json_encode([
            "status" => 200,
            "info"   => "API SST-MANAGER En línea"
        ]);
        exit;
    }

    // ---------- PERFILES ----------
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

    // ---------- PLANES ----------
    if ($table === 'planes' && $action === 'permisos') {
        $controller = new PlanModulosController($db);
        echo match ($method) {
            'GET'         => $controller->getPermisos($id),
            'POST', 'PUT' => $controller->savePermisos($id, $input),
            default       => throw new Exception("Método no permitido para planes", 405)
        };
        exit;
    }

    // ---------- TIPO EMPRESA ----------
    if ($table === 'tipo-empresa') {
        $controller = new TipoempresaController($db);
        echo match ($method) {
            'GET'    => $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no soportado", 405)
        };
        exit;
    }

    // ---------- USUARIOS ----------
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

    // ---------- CALIFICACIONES ----------
    if ($table === 'calificaciones') {
        $controller = new CalificacionController($db);
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no soportado para calificaciones", 405)
        };
        exit;
    }

    // ---------- FALLBACK ----------
    $controller = new App\Controllers\GenericController($db, $table);
    echo $controller->handleRequest($method, $id, $input);

} catch (Exception $e) {
    $code = $e->getCode();
    $http = (is_int($code) && $code >= 100 && $code <= 599) ? $code : 500;

    http_response_code($http);
    echo json_encode([
        "status" => $http,
        "error"  => $e->getMessage()
    ]);
}

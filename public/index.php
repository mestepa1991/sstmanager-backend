<?php
use App\Config\Database;
use App\Controllers\Auth\AuthController; 
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\PerfilController;
use App\Controllers\Admin\PermisoController; 
// use App\Controllers\Admin\PlanesController; // Descomenta si creas un controlador específico para Planes CRUD
use App\Controllers\Admin\PlanModulosController; // <--- Este controla la matriz (planes/permisos)

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
$uriSegments = explode('/', trim($uri, '/'));

// Ajuste para localhost/carpeta/public/tabla/accion/id
// Detectamos dinámicamente si estamos en subcarpetas
$scriptPath = dirname($_SERVER['SCRIPT_NAME']); // ej: /sstmanager-backend/public
$requestPath = str_replace($scriptPath, '', $uri); // ej: /planes/permisos/1
$pathSegments = explode('/', trim($requestPath, '/'));

// Asignación de variables basada en la ruta limpia
$table  = $_GET['table']  ?? ($pathSegments[0] ?? null);
$action = $_GET['action'] ?? ($pathSegments[1] ?? null);
$id     = $_GET['id']     ?? ($pathSegments[2] ?? null);

// Si la "accion" es numérica, entonces es un ID (ej: /planes/1)
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
    // BLOQUE 1: PERFILES (Estructura Base)
    // =======================================================
    
    // 1.1 PERMISOS DE PERFIL (/perfiles/permisos/{id})
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

    // 1.2 CRUD DE PERFILES (/perfiles)
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
    // BLOQUE 2: PLANES (Estructura Espejo a Perfiles)
    // =======================================================

    // 2.1 PERMISOS DE PLANES (/planes/permisos/{id})
    // Usa PlanModulosController (la tabla pivote)
    if ($table === 'planes' && $action === 'permisos') {
        $controller = new PlanModulosController($db);
        
        echo match ($method) {
            'GET'         => $controller->getPermisos($id), // Lee la matriz
            'POST', 'PUT' => $controller->savePermisos($id, $input), // Guarda la matriz (Sync)
            default       => throw new Exception("Método no permitido para permisos de planes", 405)
        };
        exit;
    }

    // 2.2 CRUD DE PLANES (/planes)
    // Nota: Si no tienes un PlanesController específico, el Fallback Genérico abajo lo manejará.
    // Si lo tienes, descomenta y úsalo aquí.
    /*
    if ($table === 'planes') {
        $controller = new PlanesController($db); 
        // ... lógica standard ...
        exit;
    }
    */

    // =======================================================
    // BLOQUE 3: OTROS CONTROLADORES
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
    // Maneja: /planes, /modulos, /ciclos, etc. si no tienen controlador propio
    $controller = new App\Controllers\GenericController($db, $table);
    echo $controller->handleRequest($method, $id, $input);

} catch (Exception $e) {
    // =======================================================
    // MANEJO DE ERRORES SEGURO (Fix para pantalla naranja)
    // =======================================================
    
    $exCode = $e->getCode(); // Puede ser string ('42S22') o int

    // Validamos que sea un código HTTP válido (100-599). Si no, usamos 500.
    if (is_int($exCode) && $exCode >= 100 && $exCode <= 599) {
        $httpCode = $exCode;
    } else {
        $httpCode = 500;
    }

    http_response_code($httpCode);
    
    echo json_encode([
        "status" => $httpCode,
        "error"  => $e->getMessage(),
        "debug_code" => $exCode // Muestra el código SQL original para depurar
    ]);
}
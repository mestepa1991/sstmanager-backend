<?php
use App\Config\Database;
use App\Controllers\GenericController;
use App\Controllers\Auth\AuthController;
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\CicloController;
use App\Controllers\Admin\CalificacionController;

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Configuración de Cabeceras y CORS (Siempre va primero)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Conexión a la Base de Datos
$database = new Database(); 
$db = $database->getConnection();

// 3. Captura de Parámetros
$table  = $_GET['table'] ?? null;
$action = $_GET['action'] ?? null; // <--- IMPORTANTE: Capturamos 'action'
$id     = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = json_decode(file_get_contents("php://input"), true);

// 4. ENRUTAMIENTO PRINCIPAL
try {
    // CASO 1: LOGIN (Prioridad Alta - AuthController)
    if ($action === 'login' && $method === 'POST') {
        $authController = new AuthController($db); 
        echo $authController->login($input);       
        exit; // Detenemos la ejecución aquí
    }

    // Validación: Si no es login, necesitamos una tabla obligatoriamente
    if (!$table) {
        echo json_encode(["sistema" => "SSTManager API", "estado" => "En línea. Use ?table=nombre o ?action=login"]);
        exit;
    }

    // CASO 2: USUARIOS (UsuarioController)
    if ($table === 'usuarios') {
        $controller = new UsuarioController($db);
        
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'PATCH'  => $controller->patch($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método $method no soportado", 405)
        };
    } elseif ($table === 'ciclos_phva') {
        $controller = new CicloController($db);
        
        echo match ($method) {
            'GET' => $id ? $controller->getOne($id) : $controller->getAll(),
            // Si quieres permitir crear/editar, agrega los casos POST/PUT aquí
            default => throw new Exception("Método no permitido para ciclos", 405)
        };
    }    
    // CASO 3: RESTO DEL SISTEMA (GenericController)
    else {
        // Aquí entran planes, modulos, perfiles, etc.
        $controller = new GenericController($db, $table);
        echo $controller->handleRequest($method, $id, $input);
    }
    // CASO ESPECÍFICO: CALIFICACIONES (Para ver el maestro-detalle)
    if ($table === 'calificaciones') {
    $controller = new CalificacionController($db);
    
    echo match ($method) {
        'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
        'POST'   => $controller->create($input),
        'PUT'    => $controller->update($id, $input),
        'DELETE' => $controller->delete($id),
        default  => throw new Exception("Método no permitido", 405)
    };
    exit;
}

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(["error" => $e->getMessage()]);
}
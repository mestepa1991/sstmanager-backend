<?php
use App\Config\Database;
use App\Controllers\Auth\AuthController; 
use App\Controllers\Auth\UsuarioController;
use App\Controllers\Admin\CicloController;
use App\Controllers\Admin\CalificacionController;
use App\Controllers\Admin\PerfilController;
use App\Controllers\Admin\PerfilPermisoController; 

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
 * 3. CAPTURA SEGURA DE PARÁMETROS (SOLUCIÓN A LOS WARNINGS)
 * Usamos el operador ?? para que si no vienen en la URL, valgan null sin dar error.
 */
$table  = $_GET['table'] ?? null;
$action = $_GET['action'] ?? null; 
$id     = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Captura del Body (JSON) para POST/PUT
$input = json_decode(file_get_contents("php://input"), true) ?? [];

// 4. ENRUTAMIENTO PRINCIPAL
try {
    // --- CASO ESPECIAL: LOGIN ---
    if ($action === 'login' && $method === 'POST') {
        $authController = new AuthController($db); 
        echo $authController->login($input);       
        exit;
    }

    // Validación: Si no hay tabla definida en la URL, devolvemos estado base
    if (!$table) {
        echo json_encode([
            "status" => 200,
            "sistema" => "SST-MANAGER API", 
            "estado" => "En línea"
        ]);
        exit;
    }

    // --- ENRUTAMIENTO POR TABLAS ---
    
    // 1. Perfiles y Permisos (Lógica Prioritaria)
    if ($table === 'perfiles') {
        if ($action === 'permisos') {
            $controller = new PerfilPermisoController($db);
            echo match ($method) {
                'GET'  => $controller->getPermisos($id),
                'POST' => $controller->savePermisos($id),
                default => throw new Exception("Método $method no permitido para permisos", 405)
            };
        } else {
            $controller = new PerfilController($db);
            echo match ($method) {
                'GET'    => $controller->getAll($id), 
                'POST'   => $controller->create($input),
                'PUT'    => $controller->update($id, $input),
                default  => throw new Exception("Método $method no soportado para perfiles", 405)
            };
        }
        exit; // Salimos para que no entre en el caso genérico
    }

    // 2. Usuarios
    elseif ($table === 'usuarios') {
        $controller = new UsuarioController($db); 
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'PATCH'  => $controller->patch($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método $method no soportado", 405)
        };
    } 
    // 3. Ciclos PHVA
    elseif ($table === 'ciclos_phva') {
        $controller = new CicloController($db); 
                echo match ($method) {
            'GET' => $id ? $controller->getOne($id) : $controller->getAll(), 
            default => throw new Exception("Método no permitido para ciclos", 405)
        };
    }

    // 4. Calificaciones
    elseif ($table === 'calificaciones') {
        $controller = new CalificacionController($db);
        echo match ($method) {
            'GET'    => $id ? $controller->getOne($id) : $controller->getAll(),
            'POST'   => $controller->create($input),
            'PUT'    => $controller->update($id, $input),
            'DELETE' => $controller->delete($id),
            default  => throw new Exception("Método no permitido", 405)
        };
    }

    // 5. Caso Genérico (Fallback para el resto de tablas)
    else {
        $controller = new App\Controllers\GenericController($db, $table);
        echo $controller->handleRequest($method, $id, $input);
    }

} catch (Exception $e) {
    // Manejo de errores global
    $code = ($e->getCode() >= 100 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode([
        "status" => $code,
        "error"  => $e->getMessage()
    ]);
}
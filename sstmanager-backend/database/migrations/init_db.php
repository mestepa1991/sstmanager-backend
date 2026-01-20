<?php
// Buscamos la raíz del proyecto de forma dinámica para cargar el autoload
$root = __DIR__;
while (!file_exists($root . '/vendor/autoload.php')) {
    $root = dirname($root);
}
require_once $root . '/vendor/autoload.php';
require_once $root . '/src/Config/Database.php';

use App\Config\Database;
use App\Models\Admin\ModuloModel;
use App\Models\Admin\PlanModel;
use App\Models\Admin\EmpresaModel;
use App\Models\Admin\PerfilModel;
use App\Models\Auth\UsuarioModel;

$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbName = 'sstmanager_db2'; 

echo "-------------------------------------------------\n";
echo "⚙️  ETAPA 1: VERIFICACIÓN DE BASE DE DATOS\n";
echo "-------------------------------------------------\n";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '$dbName' lista.\n\n";

    echo "-------------------------------------------------\n";
    echo "🏗️  ETAPA 2: MIGRACIÓN DE TABLAS ORGANIZADAS\n";
    echo "-------------------------------------------------\n";

// PASAMOS LA VARIABLE $dbName AL CONSTRUCTOR
$dbConfig = new Database($dbName); 
$db = $dbConfig->getConnection();

// Desactivamos llaves foráneas para evitar el error 1451
$db->exec("SET FOREIGN_KEY_CHECKS = 0;");

// Instalación en orden (Jerarquía SaaS)
(new ModuloModel($db))->install();
echo "✅ 1. Módulos configurados.\n";

(new PlanModel($db))->install();
echo "✅ 2. Planes de suscripción listos.\n";

   
    (new EmpresaModel($db))->install();
    echo "✅ 3. Tabla de Empresas (Tenants) creada.\n";

    (new PerfilModel($db))->install();
    echo "✅ 4. Perfiles y permisos por empresa listos.\n";

    (new UsuarioModel($db))->install();
    echo "✅ 5. Usuarios iniciales configurados.\n";

    echo "--------------------------------------------------\n";
    echo "✨ ¡Sistema inicializado con éxito!\n";

} catch (Exception $e) {
    die("\n❌ Error Fatal: " . $e->getMessage() . "\n");
}
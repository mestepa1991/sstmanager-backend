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
use App\Models\Admin\CicloModel;
use App\Models\Admin\CalificacionModel;
use App\Models\Admin\CategoriaModel;
use App\Models\Admin\ItemModel;
use App\Models\Admin\PermisoModel;

$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbName = 'sstmanager_db2'; 

// --- UN SOLO BLOQUE TRY PARA TODO EL PROCESO ---
try {
    echo "-------------------------------------------------\n";
    echo "⚙️  ETAPA 1: VERIFICACIÓN DE BASE DE DATOS\n";
    echo "-------------------------------------------------\n";

    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '$dbName' lista.\n\n";

    echo "-------------------------------------------------\n";
    echo "🏗️  ETAPA 2: MIGRACIÓN DE TABLAS ORGANIZADAS\n";
    echo "-------------------------------------------------\n";

    // Instanciamos la conexión dinámica
    $dbConfig = new Database($dbName); 
    $db = $dbConfig->getConnection();

    // Desactivar FK para cambios estructurales seguros
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    echo "🔄 Sincronizando estructura de tablas...\n";

    $modelos = [
        new ModuloModel($db),
        new PlanModel($db),
        new EmpresaModel($db),
        new PerfilModel($db),
        new UsuarioModel($db),
        new CicloModel($db),
        new CalificacionModel($db),
        new CategoriaModel($db),
        new ItemModel($db),
        new PermisoModel($db)
    ];

    foreach ($modelos as $index => $modelo) {
        echo "   🔹 [" . ($index + 1) . "] Procesando " . get_class($modelo) . "...\n";
        $modelo->install();
    }

    // Reactivar FK
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "--------------------------------------------------\n";
    echo "✨ Sistema sincronizado y listo para Auth.\n";

} catch (PDOException $e) {
    echo "\n❌ Error de Base de Datos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "\n❌ Error General: " . $e->getMessage() . "\n";
}
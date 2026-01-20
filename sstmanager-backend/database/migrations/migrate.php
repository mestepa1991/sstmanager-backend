<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

use App\Config\Database;
// Importamos los modelos desde sus nuevas ubicaciones organizadas
use App\Models\Admin\ModuloModel;
use App\Models\Admin\PlanModel;
use App\Models\Admin\EmpresaModel;
use App\Models\Admin\PerfilModel;
use App\Models\Auth\UsuarioModel;

try {
    $db = (new Database())->getConnection();
    echo "🚀 Iniciando migración del sistema SST-MANAGER...\n";
    echo "--------------------------------------------------\n";

    // 1. Módulos y Planes: Son la base y no dependen de nadie
    $moduloModel = new ModuloModel($db);
    $moduloModel->install();
    echo "✅ Capa 1: Módulos configurados.\n";

    $planModel = new PlanModel($db);
    $planModel->install();
    echo "✅ Capa 2: Planes y relación de módulos lista.\n";

    // 2. Empresas: Dependen de que existan los Planes
    $empresaModel = new EmpresaModel($db);
    $empresaModel->install();
    echo "✅ Capa 3: Empresas (Tenants) configuradas.\n";

    // 3. Perfiles: Dependen de las Empresas (cada empresa tiene sus perfiles)
    $perfilModel = new PerfilModel($db);
    $perfilModel->install();
    echo "✅ Capa 4: Perfiles y matriz de permisos lista.\n";

    // 4. Usuarios: Dependen de Empresas y Perfiles
    $usuarioModel = new UsuarioModel($db);
    $usuarioModel->install();
    echo "✅ Capa 5: Usuarios y seguridad base configurada.\n";

    echo "--------------------------------------------------\n";
    echo "✨ ¡Migración completada exitosamente!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO EN LA MIGRACIÓN:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
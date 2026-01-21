<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\ModuloModel;
use App\Serializers\Admin\ModuloSerializer;
use Exception;

class ModuloController extends GenericController {
      
    public function __construct($db) {
        parent::__construct($db, 'modulos');
        $this->model = new ModuloModel($db); 
    }

    
    /**
     * @OA\Get(
     * path="/index.php?table=modulos",
     * tags={"Admin - Catálogos"},
     * summary="Listar módulos",
     * description="Por defecto trae solo los activos. Usa ?todos=true para ver el historial completo (incluyendo eliminados).",
     * @OA\Parameter(
     * name="todos",
     * in="query",
     * description="Enviar 'true' para ver inactivos también",
     * required=false,
     * @OA\Schema(type="string", enum={"true", "false"})
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista de módulos",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Modulo"))
     * )
     * )
     */
    public function getAll() {
        // Lógica de Trazabilidad: Filtro Inteligente
        $mostrarTodos = isset($_GET['todos']) && $_GET['todos'] === 'true';

        if ($mostrarTodos) {
            // Trae TODO el historial (Activos e Inactivos)
            $data = $this->model->all();
        } else {
            // SQL Manual para filtrar solo estado=1 (Activos)
            $sql = "SELECT * FROM modulos WHERE estado = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return json_encode(ModuloSerializer::toList($data));
    }

    /**
     * @OA\Post(
     * path="/index.php?table=modulos",
     * tags={"Admin - Catálogos"},
     * summary="Crear módulo",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre_modulo"},
     * @OA\Property(property="nombre_modulo", type="string", example="Inventarios"),
     * @OA\Property(property="descripcion", type="string", example="Control de stock"),
     * @OA\Property(property="icono", type="string", example="fas fa-box")
     * )
     * ),
     * @OA\Response(response=201, description="Creado"),
     * @OA\Response(response=409, description="Duplicado")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['nombre_modulo'])) throw new Exception("Nombre obligatorio");

            // Verificar duplicados
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM modulos WHERE nombre_modulo = ?");
            $stmt->execute([$input['nombre_modulo']]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409);
                throw new Exception("El módulo ya existe.");
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Módulo creado"]);

        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/index.php?table=modulos&id={id}",
     * tags={"Admin - Catálogos"},
     * summary="Actualizar módulo",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre_modulo", type="string"),
     * @OA\Property(property="descripcion", type="string"),
     * @OA\Property(property="estado", type="integer", description="1 para activar, 0 para desactivar")
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        $success = $this->model->update($id, $input);
        return json_encode(["ok" => $success, "mensaje" => "Actualizado correctamente"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=modulos&id={id}",
     * tags={"Admin - Catálogos"},
     * summary="Desactivar módulo (Soft Delete)",
     * description="No elimina el registro. Cambia estado a 0 para mantener trazabilidad.",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Desactivado correctamente")
     * )
     */
    public function delete($id) {
        // AQUÍ ESTÁ LA MAGIA: Update en lugar de Delete
        $success = $this->model->update($id, ['estado' => 0]);
        
        return json_encode([
            "ok" => $success, 
            "mensaje" => $success ? "Módulo desactivado (Archivado)" : "Error al desactivar"
        ]);
    }
}
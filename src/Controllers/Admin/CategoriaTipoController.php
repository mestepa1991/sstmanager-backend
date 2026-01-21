<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CategoriaModel; // Usamos el mismo modelo que crea ambas tablas
use App\Serializers\Admin\CategoriaSerializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="CategoriaTipoRequest",
 * title="Cuerpo de Tipo de Categoría",
 * @OA\Property(property="categoria_id", type="integer", example=1, description="ID de la categoría padre"),
 * @OA\Property(property="descripcion", type="string", example="RECURSO HUMANO")
 * )
 * * @OA\Schema(
 * schema="CategoriaTipoResponse",
 * title="Respuesta de Tipo de Categoría",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="categoria_id", type="integer", example=1),
 * @OA\Property(property="descripcion", type="string", example="RECURSO HUMANO"),
 * @OA\Property(property="estado", type="integer", example=1)
 * )
 */
class CategoriaTipoController {
    private $db;
    private $model;

    public function __construct($db) {
        $this->db = $db;
        // Apuntamos a la tabla específica 'categoria_tipos'
        $this->model = new \App\Models\GenericModel($db, 'categoria_tipos');
    }

    /**
     * @OA\Post(
     * path="/index.php?table=categoria_tipos",
     * tags={"Admin - Categorias"},
     * summary="Crear nuevo tipo de categoría",
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CategoriaTipoRequest")),
     * @OA\Response(response=201, description="Creado con éxito")
     * )
     */
    public function create($input) {
        if (empty($input['categoria_id']) || empty($input['descripcion'])) {
            http_response_code(400);
            return json_encode(["error" => "Datos incompletos"]);
        }

        $id = $this->model->create([
            'categoria_id' => $input['categoria_id'],
            'descripcion'  => $input['descripcion'],
            'estado'       => 1
        ]);

        http_response_code(201);
        return json_encode(["mensaje" => "Tipo creado", "id" => $id]);
    }

    /**
     * @OA\Put(
     * path="/index.php?table=categoria_tipos&id={id}",
     * tags={"Admin - Categorias"},
     * summary="Actualizar tipo de categoría",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CategoriaTipoRequest")),
     * @OA\Response(response=200, description="Actualizado")
     * )
     */
    public function update($id, $input) {
        $data = [];
        if (!empty($input['categoria_id'])) $data['categoria_id'] = $input['categoria_id'];
        if (!empty($input['descripcion'])) $data['descripcion'] = $input['descripcion'];

        if (empty($data)) {
            http_response_code(400);
            return json_encode(["error" => "No hay datos para actualizar"]);
        }

        $success = $this->model->update($id, $data);
        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "Sin cambios"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=categoria_tipos&id={id}",
     * tags={"Admin - Categorias"},
     * summary="Inactivar tipo (Borrado Lógico)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado a 0")
     * )
     */
    public function delete($id) {
        $sql = "UPDATE categoria_tipos SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        return json_encode(["mensaje" => $success ? "Inactivado correctamente" : "Error al inactivar"]);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=categoria_tipos",
     * tags={"Admin - Categorias"},
     * summary="Listar todos los tipos activos",
     * @OA\Response(response=200, description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CategoriaTipoResponse")))
     * )
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categoria_tipos WHERE estado = 1 ORDER BY id DESC");
        $tipos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Usamos una versión simple del serializador para la lista
        $response = array_map(fn($t) => [
            'id' => (int)$t['id'],
            'categoria_id' => (int)$t['categoria_id'],
            'descripcion' => $t['descripcion'],
            'estado' => (int)$t['estado']
        ], $tipos);

        return json_encode($response);
    }
}
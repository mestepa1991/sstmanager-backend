<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CategoriaModel;
use App\Serializers\Admin\CategoriaSerializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="CategoriaRequest",
 * title="Cuerpo de Categoria",
 * description="Campos necesarios para crear o actualizar una categoría (Excluye campos automáticos)",
 * @OA\Property(property="descripcion", type="string", example="RECURSOS FINANCIEROS")
 * )
 * * @OA\Schema(
 * schema="CategoriaResponse",
 * title="Respuesta de Categoria",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="descripcion", type="string", example="RECURSOS FINANCIEROS"),
 * @OA\Property(property="estado", type="integer", example=1)
 * )
 */
class CategoriaController {
    private $db;
    private $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new CategoriaModel($db);
    }

    /**
     * @OA\Post(
     * path="/index.php?table=categorias",
     * tags={"Admin - Categorias"},
     * summary="Crear nueva categoria",
     * description="Solo requiere los campos manuales. El ID y Estado son automáticos.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/CategoriaRequest")
     * ),
     * @OA\Response(response=201, description="Categoria creada exitosamente")
     * )
     */
    public function create($input) {
        if (empty($input['descripcion'])) {
            http_response_code(400);
            return json_encode(["error" => "La descripción es obligatoria"]);
        }

        // El ID es autoincrementable y el estado se asigna en el modelo/DB
        $id = $this->model->create([
            'descripcion' => $input['descripcion'], 
            'estado' => 1 
        ]);

        http_response_code(201);
        return json_encode(["mensaje" => "Creado correctamente", "id" => $id]);
    }

    /**
     * @OA\Put(
     * path="/index.php?table=categorias&id={id}",
     * tags={"Admin - Categorias"},
     * summary="Actualizar categoria existente",
     * @OA\Parameter(
     * name="id",
     * in="query",
     * required=true,
     * description="ID autoincrementable de la categoría",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/CategoriaRequest")
     * ),
     * @OA\Response(response=200, description="Actualizado con éxito")
     * )
     */
    public function update($id, $input) {
        if (empty($input['descripcion'])) {
            http_response_code(400);
            return json_encode(["error" => "Faltan datos para actualizar"]);
        }

        $success = $this->model->update($id, ['descripcion' => $input['descripcion']]);
        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "Sin cambios realizados"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=categorias&id={id}",
     * tags={"Admin - Categorias"},
     * summary="Inactivar categoria (Borrado Lógico)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado a 0")
     * )
     */
    public function delete($id) {
        // Borrado lógico: Cambiamos estado de 1 a 0
        $sql = "UPDATE categorias SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success) {
            // Inactivación en cascada para tipos asociados
            $this->db->prepare("UPDATE categoria_tipos SET estado = 0 WHERE categoria_id = ?")
                     ->execute([$id]);
            return json_encode(["mensaje" => "Registro inactivado correctamente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al procesar la solicitud"]);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=categorias",
     * tags={"Admin - Categorias"},
     * summary="Listar categorias activas (estado 1)",
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CategoriaResponse"))
     * )
     * )
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY id DESC");
        return json_encode(CategoriaSerializer::listToArray($stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }
}
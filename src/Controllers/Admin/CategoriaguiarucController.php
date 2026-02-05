<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CategoriaGuiaRucModel;
use App\Serializers\Admin\CategoriaguiarucSerializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="CategoriaGuiaRucRequest",
 * title="Cuerpo de Categoria Guía RUC",
 * @OA\Property(property="codigo", type="string", example="CAT-01"),
 * @OA\Property(property="descripcion", type="string", example="LIDERAZGO Y COMPROMISO")
 * )
 * @OA\Schema(
 * schema="CategoriaGuiaRucResponse",
 * title="Respuesta de Categoria Guía RUC",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="codigo", type="string", example="CAT-01"),
 * @OA\Property(property="descripcion", type="string", example="LIDERAZGO Y COMPROMISO"),
 * @OA\Property(property="estado", type="integer", example=1)
 * )
 */
class CategoriaGuiaRucController {
    private $db;
    private $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new CategoriaguiarucModel($db);
    }

    /**
     * @OA\Post(
     * path="/index.php?table=categorias_guia_ruc",
     * tags={"Admin - Categorias Guia RUC"},
     * summary="Crear nueva categoria para Guía RUC",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/CategoriaGuiaRucRequest")
     * ),
     * @OA\Response(response=201, description="Creado exitosamente")
     * )
     */
    public function create($input) {
        if (empty($input['codigo']) || empty($input['descripcion'])) {
            http_response_code(400);
            return json_encode(["error" => "El código y la descripción son obligatorios"]);
        }

        $id = $this->model->create([
            'codigo'      => trim($input['codigo']), 
            'descripcion' => trim($input['descripcion']), 
            'estado'      => 1 
        ]);

        http_response_code(201);
        return json_encode(["mensaje" => "Creado correctamente", "id" => $id]);
    }

    /**
     * @OA\Put(
     * path="/index.php?table=categorias_guia_ruc&id={id}",
     * tags={"Admin - Categorias Guia RUC"},
     * summary="Actualizar categoria de Guía RUC",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/CategoriaGuiaRucRequest")
     * ),
     * @OA\Response(response=200, description="Actualizado con éxito")
     * )
     */
    public function update($id, $input) {
        if (empty($input['codigo']) || empty($input['descripcion'])) {
            http_response_code(400);
            return json_encode(["error" => "Faltan datos para actualizar"]);
        }

        $success = $this->model->update($id, [
            'codigo'      => trim($input['codigo']),
            'descripcion' => trim($input['descripcion'])
        ]);

        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "Sin cambios realizados"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=categorias_guia_ruc&id={id}",
     * tags={"Admin - Categorias Guia RUC"},
     * summary="Inactivar categoria (Borrado Lógico)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Registro inactivado")
     * )
     */
    public function delete($id) {
        $sql = "UPDATE categorias_guia_ruc SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success) {
            return json_encode(["mensaje" => "Registro inactivado correctamente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al procesar la solicitud"]);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=categorias_guia_ruc",
     * tags={"Admin - Categorias Guia RUC"},
     * summary="Listar categorias de Guía RUC activas",
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CategoriaGuiaRucResponse"))
     * )
     * )
     */
    public function getAll() {
        $sql = "SELECT * FROM categorias_guia_ruc WHERE estado = 1 ORDER BY codigo ASC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return json_encode(CategoriaguiarucSerializer::listToArray($data));
    }
}
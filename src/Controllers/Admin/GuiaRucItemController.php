<?php
namespace App\Controllers\Admin;

use App\Models\Admin\GuiaRucItemModel;
use App\Serializers\Admin\GuiaRucItemSerializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="GuiaRucItemRequest",
 * title="Cuerpo de Ítem Guía RUC",
 * @OA\Property(property="id_categoria", type="integer", example=1),
 * @OA\Property(property="num_item", type="string", example="1.1.1"),
 * @OA\Property(property="requisito", type="string", example="Política SSTA"),
 * @OA\Property(property="descripcion", type="string", example="Descripción detallada..."),
 * @OA\Property(property="observaciones", type="string", example="Notas adicionales")
 * )
 */
class GuiaRucItemController {
    private $db;
    private $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new GuiaRucItemModel($db);
    }

    /**
     * @OA\Post(
     * path="/index.php?table=guia_ruc_items",
     * tags={"Admin - Ítems Guía RUC"},
     * summary="Crear nuevo ítem de la Guía RUC",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/GuiaRucItemRequest")
     * ),
     * @OA\Response(response=201, description="Creado exitosamente")
     * )
     */
    public function create($input) {
        if (empty($input['id_categoria']) || empty($input['num_item']) || empty($input['requisito'])) {
            http_response_code(400);
            return json_encode(["error" => "Categoría, Ítem y Requisito son campos obligatorios"]);
        }

        $id = $this->model->create([
            'id_categoria'  => (int)$input['id_categoria'],
            'num_item'      => trim($input['num_item']),
            'requisito'     => trim($input['requisito']),
            'descripcion'   => trim($input['descripcion'] ?? ''),
            'observaciones' => trim($input['observaciones'] ?? ''),
            'estado'        => 1
        ]);

        http_response_code(201);
        return json_encode(["mensaje" => "Ítem creado correctamente", "id" => $id]);
    }

    /**
     * @OA\Put(
     * path="/index.php?table=guia_ruc_items&id={id}",
     * tags={"Admin - Ítems Guía RUC"},
     * summary="Actualizar ítem de Guía RUC",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/GuiaRucItemRequest")
     * ),
     * @OA\Response(response=200, description="Actualizado con éxito")
     * )
     */
    public function update($id, $input) {
        if (empty($input['id_categoria']) || empty($input['num_item'])) {
            http_response_code(400);
            return json_encode(["error" => "Faltan datos esenciales para la actualización"]);
        }

        $success = $this->model->update($id, [
            'id_categoria'  => (int)$input['id_categoria'],
            'num_item'      => trim($input['num_item']),
            'requisito'     => trim($input['requisito']),
            'descripcion'   => trim($input['descripcion'] ?? ''),
            'observaciones' => trim($input['observaciones'] ?? '')
        ]);

        return json_encode(["mensaje" => $success ? "Actualizado correctamente" : "No se realizaron cambios"]);
    }

    /**
     * @OA\Delete(
     * path="/index.php?table=guia_ruc_items&id={id}",
     * tags={"Admin - Ítems Guía RUC"},
     * summary="Inactivar ítem (Borrado Lógico)",
     * @OA\Parameter(name="id", in="query", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Ítem inactivado")
     * )
     */
    public function delete($id) {
        $sql = "UPDATE guia_ruc_items SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        return json_encode(["mensaje" => $success ? "Registro inactivado" : "Error al inactivar"]);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=guia_ruc_items",
     * tags={"Admin - Ítems Guía RUC"},
     * summary="Listar todos los ítems con su categoría",
     * @OA\Response(
     * response=200, 
     * description="OK", 
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/GuiaRucItemSerializer"))
     * )
     * )
     */
    public function getAll() {
        // Usamos el método con JOIN que definimos en el Modelo
        $data = $this->model->getItemsConCategoria();
        return json_encode(GuiaRucItemSerializer::listToArray($data));
    }
}
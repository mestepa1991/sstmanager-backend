<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\ItemModel;
use App\Serializers\Admin\ItemSerializer;
use Exception;

class ItemController extends GenericController {

    public function __construct($db) {
        // El modelo ItemModel gestiona la tabla 'item' (hija)
        parent::__construct($db, 'item');
        $this->model = new ItemModel($db);
    } 

    /**
     * @OA\Get(
     * path="/item",
     * operationId="getItemsList",
     * tags={"Configuración - Items"},
     * summary="Listar items de estándares",
     * description="Obtiene todos los items o filtra por una categoría específica.",
     * @OA\Parameter(
     * name="id_categoria",
     * in="query",
     * description="ID de la tabla padre (categoria_tipos) para filtrar",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=15),
     * @OA\Property(property="idCategoria", type="integer", example=2),
     * @OA\Property(property="itemEstandar", type="string", example="1.1.2"),
     * @OA\Property(property="item", type="string", example="Pago de Seguridad Social"),
     * @OA\Property(property="criterio", type="string", example="Verificar planillas"),
     * @OA\Property(property="modoVerificacion", type="string", example="Documental"),
     * @OA\Property(property="estado", type="string", example="Activo")
     * )
     * )
     * )
     * )
     */
    public function getAll() {
        $idCategoria = $_GET['id_categoria'] ?? null;

        if ($idCategoria) {
            // Filtramos por la llave foránea si viene en la URL
            $sql = "SELECT * FROM item WHERE id_categorias = ? AND estado = 'Activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCategoria]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            // Si no hay filtro, traemos todo (usando lógica del modelo o genérica)
            // Asumimos que queremos solo los activos por defecto
            $sql = "SELECT * FROM item WHERE estado = 'Activo'";
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Usamos el serializador creado anteriormente
        return json_encode(ItemSerializer::formatList($data));
    }

    /**
     * @OA\Post(
     * path="/item",
     * operationId="createItem",
     * tags={"Configuración - Items"},
     * summary="Crear nuevo item",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_categorias", "item"},
     * @OA\Property(property="id_categorias", type="integer", example=2, description="ID del Padre"),
     * @OA\Property(property="item_estandar", type="string", example="1.1.2"),
     * @OA\Property(property="item", type="string", example="Nombre del requisito"),
     * @OA\Property(property="criterio", type="string", example="Criterio a cumplir"),
     * @OA\Property(property="modo_verificacion", type="string", example="Documental")
     * )
     * ),
     * @OA\Response(response=201, description="Item creado correctamente"),
     * @OA\Response(response=400, description="Error de validación")
     * )
     */
    public function create($input) {
        try {
            // Validaciones básicas
            if (empty($input['id_categorias'])) {
                throw new Exception("El ID de la categoría (padre) es obligatorio.");
            }
            if (empty($input['item'])) {
                throw new Exception("El nombre del item es obligatorio.");
            }

            // Asignar estado por defecto si no viene
            if (!isset($input['estado'])) {
                $input['estado'] = 'Activo';
            }

            $id = $this->model->create($input);
            return json_encode(["id" => $id, "mensaje" => "Item registrado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/item/{id}",
     * operationId="updateItem",
     * tags={"Configuración - Items"},
     * summary="Actualizar item existente",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID del item (id_detalle)",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="item_estandar", type="string"),
     * @OA\Property(property="item", type="string"),
     * @OA\Property(property="criterio", type="string"),
     * @OA\Property(property="modo_verificacion", type="string"),
     * @OA\Property(property="estado", type="string", example="Activo")
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado"),
     * @OA\Response(response=400, description="Error al actualizar")
     * )
     */
    public function update($id, $input) {
        try {
            // Nota: En tu modelo la PK es 'id_detalle', el GenericModel debería saber manejar esto
            // si el método update usa la PK definida en el modelo.
            $success = $this->model->update($id, $input);
            
            if (!$success) {
                throw new Exception("No se pudo actualizar el registro o no hubo cambios.");
            }

            return json_encode(["ok" => true, "mensaje" => "Item actualizado correctamente"]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/item/{id}",
     * operationId="deleteItem",
     * tags={"Configuración - Items"},
     * summary="Desactivar (eliminar lógicamente) un item",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Item desactivado")
     * )
     */
    public function delete($id) {
        // En tu tabla 'item', el estado es VARCHAR, así que usamos 'Inactivo' en lugar de 0
        $success = $this->model->update($id, ['estado' => 'Inactivo']);
        return json_encode(["ok" => $success, "mensaje" => "Item desactivado correctamente"]);
    }
}
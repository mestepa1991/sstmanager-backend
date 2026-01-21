<?php
namespace App\Controllers\Admin;

use App\Models\Admin\CicloModel;
use App\Serializers\Admin\CicloSerializer;
use OpenApi\Annotations as OA; // Importante para que el editor reconozca las etiquetas

/**
 * @OA\Schema(
 * schema="Ciclo",
 * title="Ciclo PHVA",
 * description="Modelo de los pasos del ciclo PHVA",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="nombre", type="string", example="Planear")
 * )
 */
class CicloController {
    private $model;

    public function __construct($db) {
        $this->model = new CicloModel($db);
    }

    /**
     * @OA\Get(
     * path="/index.php?table=ciclos_phva",
     * tags={"Admin - Catálogos"},
     * summary="Listar todos los ciclos PHVA",
     * description="Devuelve la lista para llenar el select 'Ciclo al PHVA' del formulario.",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Ciclo")
     * )
     * ),
     * @OA\Response(response=500, description="Error interno")
     * )
     */
    public function getAll() {
        try {
            $ciclos = $this->model->all(); 
            $response = CicloSerializer::toArrayMany($ciclos);
            return json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode(["error" => "Error al cargar ciclos: " . $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     * path="/index.php?table=ciclos_phva&id={id}",
     * tags={"Admin - Catálogos"},
     * summary="Obtener un ciclo por ID",
     * @OA\Parameter(
     * name="id",
     * in="query",
     * description="ID del ciclo a buscar",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Ciclo encontrado",
     * @OA\JsonContent(ref="#/components/schemas/Ciclo")
     * ),
     * @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function getOne($id) {
        $ciclo = $this->model->find($id);
        if ($ciclo) {
            return json_encode(CicloSerializer::toArray($ciclo));
        }
        http_response_code(404);
        return json_encode(["error" => "Ciclo no encontrado"]);
    }
}
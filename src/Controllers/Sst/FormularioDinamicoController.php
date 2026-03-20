<?php
namespace App\Controllers\Sst;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Sst\FormularioDinamicoModel;
use App\Models\Sst\PlantillaSstModel;
use App\Serializers\Sst\FormularioSerializer;
use Exception;

/**
 * @OA\Tag(name="SST - Formularios Dinámicos", description="Gestión de formatos dinámicos por ítem")
 */
class FormularioDinamicoController extends GenericController {

    private $modelPlantilla;

    public function __construct($db) {
        parent::__construct($db, 'sst_formularios_personalizados');
        $this->model = new FormularioDinamicoModel($db);
        $this->modelPlantilla = new PlantillaSstModel($db);
    }

    /**
     * @OA\Get(
     * path="/formularios-dinamicos/empresa/{id_empresa}/item/{id_item}",
     * tags={"SST - Formularios Dinámicos"},
     * summary="Obtener formulario dinámico por empresa e ítem",
     * @OA\Parameter(name="id_empresa", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="id_item", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Formulario cargado con éxito")
     * )
     */
    public function getPorEmpresa($idEmpresa, $idItem) {
        // Limpiamos cualquier salida previa para asegurar un JSON puro
        if (ob_get_length()) ob_clean(); 

        try {
            if (!$idItem) throw new Exception("ID de ítem requerido");

            // 1. Buscar personalización de la empresa para este ítem específico
            $personalizado = $this->model->getByEmpresaItem((int)$idEmpresa, (int)$idItem);
            
            $plantillaMaestra = null;
            if (!$personalizado) {
                // 2. Si no hay cambios, cargar la plantilla Macro (AN-SST-33 u otros) [cite: 13]
                $plantillaMaestra = $this->modelPlantilla->getByItem((int)$idItem);
            }

            // 3. Serializar respuesta combinando base legal y datos de empresa
            $result = FormularioSerializer::format($personalizado, $plantillaMaestra, (int)$idItem);

            header('Content-Type: application/json');
            echo json_encode([
                "ok" => true,
                "id_empresa" => $idEmpresa,
                "data" => $result
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => $e->getMessage()]);
            exit;
        }
    }

    /**
     * @OA\Post(
     * path="/formularios-dinamicos/guardar",
     * tags={"SST - Formularios Dinámicos"},
     * summary="Guardar personalización de formulario",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_empresa", "id_item_sst", "datos"},
     * @OA\Property(property="id_empresa", type="integer"),
     * @OA\Property(property="id_item_sst", type="integer"),
     * @OA\Property(property="datos", type="object")
     * )
     * ),
     * @OA\Response(response=200, description="Guardado exitoso")
     * )
     */
    public function save($input) {
        if (ob_get_length()) ob_clean();

        try {
            if (empty($input['id_empresa'])) throw new Exception("ID empresa requerido");
            if (empty($input['id_item_sst'])) throw new Exception("ID ítem SST requerido");

            $success = $this->model->saveFormContent(
                (int)$input['id_empresa'], 
                (int)$input['id_item_sst'], 
                $input['datos'], 
                (int)($_SESSION['id_usuario'] ?? 0)
            );

            header('Content-Type: application/json');
            echo json_encode([
                "ok" => true,
                "mensaje" => "Formulario actualizado correctamente"
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => $e->getMessage()]);
            exit;
        }
    }
}
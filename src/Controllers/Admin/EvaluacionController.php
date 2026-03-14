<?php

namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Services\SSTCalculador;
use Exception;

/**
 * @OA\Tag(name="Evaluaciones SG-SST", description="Gestión de evaluaciones basadas en Resolución 0312")
 */
class EvaluacionController 
{
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @OA\Post(
     * path="/evaluaciones/generar",
     * operationId="generarEvaluacionSGSST",
     * tags={"Evaluaciones SG-SST"},
     * summary="Generar evaluación automática (Filtro Res 0312)",
     * description="Calcula y asigna los ítems de la Resolución 0312 según nivel de riesgo y número de empleados.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_empresa", "cantidad_empleados", "clase_riesgo"},
     * @OA\Property(property="id_empresa", type="integer", example=1),
     * @OA\Property(property="cantidad_empleados", type="integer", example=8),
     * @OA\Property(property="clase_riesgo", type="integer", example=1)
     * )
     * ),
     * @OA\Response(
     * response=200, 
     * description="Evaluación generada con éxito"
     * )
     * )
     */
    public function create($input) {

    try {

        if (empty($input['id_empresa'])) {
            throw new Exception("ID empresa requerido");
        }

        $idEmpresa = $input['id_empresa'];
        $numEmpleados = (int)($input['cantidad_empleados'] ?? 0);
        $riesgo = (int)($input['clase_riesgo'] ?? 0);

        $sqlItems = "SELECT * FROM item";
        $stmtItems = $this->db->query($sqlItems);
        $todosLosItems = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

        $calculador = new SSTCalculador();
        $resultado = $calculador->obtenerItemsLegales($todosLosItems, $numEmpleados, $riesgo);

        if (empty($resultado['items'])) {
            throw new Exception("No se generaron items.");
        }

        $this->db->beginTransaction();

        $sqlEval = "INSERT INTO evaluaciones
        (id_empresa,tipo_estandar,nombre_categoria,cantidad_empleados,clase_riesgo,fecha)
        VALUES (?,?,?,?,?,NOW())";

        $stmtEval = $this->db->prepare($sqlEval);

        $stmtEval->execute([
            $idEmpresa,
            $resultado['codigo_std'],
            $resultado['categoria'],
            $numEmpleados,
            $riesgo
        ]);

        $evalId = $this->db->lastInsertId();

        $sqlDetalle = "INSERT INTO evaluacion_detalles
        (id_evaluacion,id_item_maestro)
        VALUES (?,?)";

        $stmtDetalle = $this->db->prepare($sqlDetalle);

        foreach ($resultado['items'] as $item) {

            if (!empty($item['id_detalle'])) {

                $stmtDetalle->execute([
                    $evalId,
                    $item['id_detalle']
                ]);
            }
        }

        $this->db->commit();

        return json_encode([
            "ok"=>true,
            "mensaje"=>"Evaluación creada",
            "id_evaluacion"=>$evalId
        ]);

    } catch (Exception $e) {

        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        http_response_code(400);

        return json_encode([
            "ok"=>false,
            "error"=>$e->getMessage()
        ]);
    }
 }
/**
     * @OA\Get(
     * path="/evaluaciones/empresa/{id_empresa}",
     * tags={"Evaluaciones SG-SST"},
     * summary="Obtener el formulario de autoevaluación dinámico por empresa",
     * @OA\Parameter(name="id_empresa", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Formulario dinámico con datos de empresa e ítems")
     * )
     */
    public function getFormularioPorEmpresa($idEmpresa) {
        if (ob_get_length()) ob_clean();

        try {
            // 1. Consultar datos de la empresa y su última evaluación generada
            $sqlEmpresa = "SELECT 
                                ev.id_evaluacion,
                                ev.nombre_categoria as estandar_aplicado,
                                ev.cantidad_empleados,
                                ev.clase_riesgo
                           FROM  evaluaciones ev 
                           WHERE ev.id_empresa = ? 
                           ORDER BY ev.id_evaluacion DESC LIMIT 1";
            
            $stmtEmp = $this->db->prepare($sqlEmpresa);
            $stmtEmp->execute([$idEmpresa]);
            $empresaData = $stmtEmp->fetch(\PDO::FETCH_ASSOC);

            if (!$empresaData) throw new Exception("Empresa no encontrada.");
            if (!$empresaData['id_evaluacion']) throw new Exception("Esta empresa no tiene una evaluación generada aún.");

            // 2. Consultar los ítems de esa evaluación uniendo con la tabla 'item' 
            // y su categoría (Ciclo) para el formulario dinámico
            $sqlItems = "SELECT 
                            ed.id_detalle,
                            i.item_estandar,
                            i.item as descripcion_item,
                            i.criterio,
                            i.modo_verificacion,
                            ed.cumple, -- Aquí guardaremos: Cumple, No Cumple, No Aplica
                            ed.observaciones,
                            c.descripcion as ciclo 
                         FROM evaluacion_detalles ed
                         JOIN item i ON ed.id_item_maestro = i.id_detalle
                         JOIN categoria_tipos c ON i.id_categorias = c.id
                         WHERE ed.id_evaluacion = ?
                         ORDER BY i.id_detalle ASC";
            
            $stmtItems = $this->db->prepare($sqlItems);
            $stmtItems->execute([$empresaData['id_evaluacion']]);
            $items = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                "ok" => true,
                "info_general" => $empresaData,
                "formulario" => $items
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * @OA\Put(
     * path="/evaluaciones/calificar/{id_detalle_evaluacion}",
     * tags={"Evaluaciones SG-SST"},
     * summary="Actualizar calificación (Entero) y observaciones (Texto)",
     * @OA\Parameter(name="id_detalle_evaluacion", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="cumple", type="integer", example=1, description="1: Cumple, 2: No cumple, 3: No aplica"),
     * @OA\Property(property="observaciones", type="string", example="Descripción detallada de la evidencia")
     * )
     * ),
     * @OA\Response(response=200, description="Dato guardado")
     * )
     */
    public function updateCalificacion($id, $input) {
        if (ob_get_length()) ob_clean();

        try {
            // Forzamos que 'cumple' sea un entero (int)
            $cumple = isset($input['cumple']) ? (int)$input['cumple'] : null;
            
            // Forzamos que 'observaciones' sea texto
            $observaciones = isset($input['observaciones']) ? (string)$input['observaciones'] : '';

            // Si el campo en tu base de datos se llama 'descripcion' en lugar de 'observaciones',
            // solo cambia el nombre en la línea de abajo.
            $sql = "UPDATE evaluacion_detalles SET 
                        cumple = ?, 
                        observaciones = ? 
                    WHERE id_detalle = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cumple, $observaciones, (int)$id]);

            header('Content-Type: application/json');
            echo json_encode([
                "ok" => true, 
                "mensaje" => "Calificación #" . $id . " guardada correctamente"
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => $e->getMessage()]);
            exit;
        }
    }

}
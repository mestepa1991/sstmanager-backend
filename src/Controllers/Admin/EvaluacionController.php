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
}
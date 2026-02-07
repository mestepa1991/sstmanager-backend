<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\PersonalSstModel;
use App\Serializers\Admin\PersonalSstSerializer;
use Exception;

class PersonalSstController extends GenericController {
  
    public function __construct($db) {
        // Aseguramos el uso de guion bajo para evitar Error 1146 (Tabla no encontrada)
        parent::__construct($db, 'personal_sst');
        $this->model = new PersonalSstModel($db); 
    }

   /**
     * @OA\Get(
     * path="/personal_sst/empresa/{id_empresa}",
     * tags={"Admin - Personal SST"},
     * summary="Obtener personal SST filtrando estrictamente por campo id_empresa",
     * @OA\Parameter(name="id_empresa", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos del personal SST")
     * )
     */
    public function getByEmpresa($id_empresa) {
        try {
            if (empty($id_empresa)) {
                throw new Exception("El ID de la empresa es requerido para la consulta.");
            }

            // EXPLICACIÓN: Forzamos el WHERE sobre la columna id_empresa
            // No usamos $this->model->find() porque ese método busca por id_personal_sst
            $sql = "SELECT * FROM `personal_sst` WHERE `id_empresa` = :id_empresa AND `estado` = 1 LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            // Vinculamos el parámetro para asegurar que se trate como entero
            $stmt->bindValue(':id_empresa', $id_empresa, \PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Si no hay data para esa empresa, devolvemos success con data null
            if (!$data) {
                return json_encode([
                    "status" => "success",
                    "data" => null,
                    "mensaje" => "No se encontró personal SST asociado a la empresa ID $id_empresa"
                ]);
            }

            // Retornamos la data serializada
            return json_encode([
                "status" => "success",
                "data" => PersonalSstSerializer::toArray($data)
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     * path="/personal_sst",
     * tags={"Admin - Personal SST"},
     * summary="Listar todo el personal SST",
     * @OA\Response(
     * response=200, 
     * description="Lista de personal con su empresa asociada",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PersonalSst"))
     * )
     * )
     */
    public function getAll() {
        $sql = "SELECT p.*, e.nombre_empresa 
                FROM `personal_sst` p 
                INNER JOIN `empresas` e ON p.id_empresa = e.id_empresa 
                WHERE p.estado = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(PersonalSstSerializer::toList($data));
    }

    /**
     * @OA\Post(
     * path="/personal_sst",
     * tags={"Admin - Personal SST"},
     * summary="Asociar nuevo profesional SST a una empresa",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_empresa", "proyecto_rige", "nombre_profesional", "correo_sst"},
     * @OA\Property(property="id_empresa", type="integer", example=2),
     * @OA\Property(property="proyecto_rige", type="string", example="IMPLEMENTACIÓN ESTÁNDARES MÍNIMOS"),
     * @OA\Property(property="nombre_profesional", type="string", example="JUAN SEBASTIÁN PINEDA"),
     * @OA\Property(property="correo_sst", type="string", example="profesional.sst@ejemplo.com"),
     * @OA\Property(property="telefono_sst", type="string", example="315 888 9900"),
     * @OA\Property(property="firma_sst_url", type="string", example="JS_PINEDA_SIGN")
     * )
     * ),
     * @OA\Response(response=201, description="Personal asociado exitosamente")
     * )
     */
    public function create($input) {
        try {
            if (empty($input['id_empresa'])) throw new Exception("Debe seleccionar una empresa.");
            if (empty($input['nombre_profesional'])) throw new Exception("El nombre del profesional es requerido.");
            if (empty($input['correo_sst'])) throw new Exception("El correo de contacto es requerido.");

            $id = $this->model->create($input);
            
            http_response_code(201);
            return json_encode([
                "status" => "success", 
                "id" => $id, 
                "mensaje" => "Personal SST asociado correctamente"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     * path="/personal_sst/{id}",
     * tags={"Admin - Personal SST"},
     * summary="Actualizar datos del personal SST",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="proyecto_rige", type="string"),
     * @OA\Property(property="nombre_profesional", type="string"),
     * @OA\Property(property="correo_sst", type="string"),
     * @OA\Property(property="telefono_sst", type="string"),
     * @OA\Property(property="firma_sst_url", type="string")
     * )
     * ),
     * @OA\Response(response=200, description="Datos actualizados")
     * )
     */
    public function update($id, $input) {
        try {
            if (empty($id)) throw new Exception("ID de registro faltante.");

            $existe = $this->model->find($id);
            if (!$existe) {
                http_response_code(404);
                throw new Exception("El registro de personal no existe.");
            }

            $success = $this->model->update($id, $input);
            return json_encode([
                "ok" => $success, 
                "mensaje" => "Información de SST actualizada"
            ]);
        } catch (Exception $e) {
            if (http_response_code() === 200) http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     * path="/personal_sst/{id}",
     * tags={"Admin - Personal SST"},
     * summary="Eliminar asociación de personal",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Asociación eliminada")
     * )
     */
    public function delete($id) {
        $success = $this->model->delete($id);
        return json_encode(["ok" => $success, "mensaje" => "Registro eliminado correctamente"]);
    }
}
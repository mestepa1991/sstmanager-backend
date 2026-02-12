<?php
namespace App\Controllers\Admin;

use OpenApi\Annotations as OA;
use App\Controllers\GenericController;
use App\Models\Admin\EmpresaModel;
use App\Serializers\Admin\EmpresaSerializer;
use Exception;

class EmpresaController extends GenericController {

    public function __construct($db) {
        parent::__construct($db, 'empresas');
        $this->model = new EmpresaModel($db);
    }

    public function getAll() {
        $sql = "SELECT e.*, p.nombre_plan
                FROM empresas e
                LEFT JOIN planes p ON e.id_plan = p.id_plan
                WHERE e.estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode(EmpresaSerializer::toList($data));
    }

    public function getOne($id) {
        $sql = "SELECT e.*, p.nombre_plan FROM empresas e
                LEFT JOIN planes p ON e.id_plan = p.id_plan
                WHERE e.id_empresa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            http_response_code(404);
            return json_encode(["error" => "Empresa no encontrada"]);
        }

        return json_encode(EmpresaSerializer::toArray($data));
    }

    public function create($input) {
        try {
            // Compatibilidad: si llega "nit", lo convertimos a "numero_documento"
            if (!isset($input['numero_documento']) && isset($input['nit'])) {
                $input['numero_documento'] = $input['nit'];
                unset($input['nit']);
            }

            if (empty($input['nombre_empresa']) || empty($input['numero_documento']) || empty($input['id_plan'])) {
                throw new Exception("Nombre, Número de documento e ID Plan son obligatorios.");
            }

            // Unicidad por número_documento
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE numero_documento = ?");
            $stmt->execute([$input['numero_documento']]);
            if ($stmt->fetchColumn() > 0) throw new Exception("El número de documento ya está registrado.");

            $id = $this->model->create($input);

            http_response_code(201);
            return json_encode([
                "status" => "success",
                "id" => $id,
                "mensaje" => "Empresa registrada exitosamente"
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function update($id, $input) {
        try {
            // Compatibilidad: si llega "nit", lo convertimos a "numero_documento"
            if (!isset($input['numero_documento']) && isset($input['nit'])) {
                $input['numero_documento'] = $input['nit'];
                unset($input['nit']);
            }

            if (isset($input['numero_documento'])) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM empresas WHERE numero_documento = ? AND id_empresa != ?");
                $stmt->execute([$input['numero_documento'], $id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("El número de documento ya pertenece a otra empresa.");
            }

            $success = $this->model->update($id, $input);
            return json_encode(["ok" => (bool)$success, "mensaje" => "Información actualizada"]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    public function delete($id) {
        $success = $this->model->update($id, ['estado' => 0]);
        return json_encode(["ok" => (bool)$success, "mensaje" => "Empresa inactivada"]);
    }
}

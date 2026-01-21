<?php
namespace App\Controllers;

use App\Models\GenericModel;

class GenericController {
    protected $db;
    protected $table;
    protected $model; 

    /**
     * @param $db Conexión a la base de datos
     * @param $table Nombre de la tabla a gestionar dinámicamente
     */
    public function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
        // Instancia por defecto del motor CRUD genérico
        $this->model = new GenericModel($db, $table);
    }

    /**
     * Orquestador principal de peticiones utilizado en index.php
     */
    public function handleRequest($method, $id, $input) {
        switch ($method) {
            case 'GET': 
                return $id ? $this->getOne($id) : $this->getAll();
            case 'POST': 
                return $this->create($input);
            case 'PUT': 
                return $this->update($id, $input);
            case 'DELETE': 
                return $this->delete($id);
            default: 
                http_response_code(405);
                return json_encode(["error" => "Método $method no permitido"]);
        }
    }

    protected function getAll() {
        $data = $this->model->all();
        return json_encode($data);
    }

    protected function getOne($id) {
        $data = $this->model->find($id);
        if (!$data) {
            http_response_code(404);
            return json_encode(["error" => "Registro no encontrado en la tabla {$this->table}"]);
        }
        return json_encode($data);
    }

    // --- AQUÍ ESTABA EL ERROR: ESTA FUNCIÓN AHORA ESTÁ LIMPIA ---
    public function create($input) {
        if (empty($input)) {
            http_response_code(400);
            return json_encode(["error" => "No se enviaron datos para crear"]);
        }

        // Llamamos al Modelo. Él es quien sabe de SQL.
        $id = $this->model->create($input);
        
        if ($id) {
            http_response_code(201);
            return json_encode(["mensaje" => "Registro creado con éxito", "id" => $id]);
        }

        // Si falló, devolvemos error (el detalle real estará en los logs o si editamos el Modelo)
        http_response_code(500);
        return json_encode(["error" => "Error al insertar en la tabla {$this->table}"]);
    }

    public function update($id, $input) {
        if (!$id || empty($input)) {
            http_response_code(400);
            return json_encode(["error" => "ID o datos faltantes para actualizar"]);
        }

        $success = $this->model->update($id, $input);
        if ($success) {
            return json_encode(["mensaje" => "Registro actualizado correctamente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al actualizar en la tabla {$this->table}"]);
    }

    public function delete($id) {
        if (!$id) {
            http_response_code(400);
            return json_encode(["error" => "ID requerido para eliminar"]);
        }

        $success = $this->model->delete($id);
        if ($success) {
            return json_encode(["mensaje" => "Registro eliminado permanentemente"]);
        }

        http_response_code(500);
        return json_encode(["error" => "Error al eliminar de la tabla {$this->table}"]);
    }
}
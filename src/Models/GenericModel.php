<?php
namespace App\Models;

use PDO;
use Exception;

class GenericModel {
    protected $db;
    protected $table;
    protected $primaryKey;

    public function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
        // Definimos la PK: si es la tabla usuarios es id_usuario, sino id
        $this->primaryKey = ($this->table === 'usuarios') ? 'id_usuario' : 'id';
    }

    /**
     * READ: Obtener todos los registros
     */
    public function all() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ: Obtener un registro por ID
     */
    public function find($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE {$this->primaryKey} = :id LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * CREATE: Insertar un nuevo registro
     */
    public function create($data) {
        // 1. Prepara las columnas y valores
        $keys = implode(", ", array_keys($data));
        $values = array_values($data);
        $placeholders = implode(", ", array_fill(0, count($values), "?"));

        $sql = "INSERT INTO {$this->table} ($keys) VALUES ($placeholders)";

        // 2. Aquí es donde ponemos la trampa para ver el error
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // ¡ESTO ES LO QUE TE MOSTRARÁ EL ERROR EN SWAGGER!
            die(json_encode([
                "error_critico" => "Fallo SQL en GenericModel",
                "mensaje" => $e->getMessage(),
                "sql_intentado" => $sql
            ]));
        }
    }

    /**
     * UPDATE: Actualizar un registro existente
     */
    public function update($id, $data) {
        try {
            $fields = "";
            foreach ($data as $key => $value) {
                $fields .= "$key = :$key, ";
            }
            $fields = rtrim($fields, ", ");

            $query = "UPDATE " . $this->table . " SET $fields WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($query);

            foreach ($data as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':id', $id);

            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * DELETE: Eliminar un registro
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function insert($data)
{
    $fields = array_keys($data);
    $columns = implode(',', $fields);
    $placeholders = ':' . implode(',:', $fields);

    $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
    $stmt = $this->db->prepare($sql);

    return $stmt->execute($data);
}
    /**
 * MIGRATION: Crear tabla si no existe
 */
public function createTable(array $fields): void
{
    if (empty($fields)) {
        throw new Exception("No se definieron campos para la tabla {$this->table}");
    }

    // PK dinámica según tu convención
    $primaryKey = ($this->table === 'usuarios')
        ? 'id_usuario INT AUTO_INCREMENT PRIMARY KEY'
        : 'id INT AUTO_INCREMENT PRIMARY KEY';

    $columns = [$primaryKey];

    foreach ($fields as $name => $definition) {
        $columns[] = "$name $definition";
    }

    $sql = sprintf(
        "CREATE TABLE IF NOT EXISTS %s (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        $this->table,
        implode(', ', $columns)
    );

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
}
}
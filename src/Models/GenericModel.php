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
        
        // 1. DEFINICIÓN DINÁMICA DE LLAVE PRIMARIA
        // Detecta automáticamente la PK según la tabla para evitar errores SQL
        if ($this->table === 'usuarios') {
            $this->primaryKey = 'id_usuario';
        } elseif ($this->table === 'planes') {
            $this->primaryKey = 'id_plan';
        } elseif ($this->table === 'perfiles') {
            $this->primaryKey = 'id_perfil';
        }elseif ($this->table === 'modulos') {
            $this->primaryKey = 'id_modulo';
        }  else {

        
            $this->primaryKey = 'id';
        }
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
     * CREATE: Insertar registro(s) con soporte para inserción masiva
     */
    public function create($data) {
        // Soporte para Bulk Insert (necesario para sincronizar permisos)
        if (isset($data[0]) && is_array($data[0])) {
            $ids = [];
            try {
                $this->db->beginTransaction();
                foreach ($data as $row) {
                    $ids[] = $this->create($row);
                }
                $this->db->commit();
                return $ids;
            } catch (\PDOException $e) {
                $this->db->rollBack();
                throw $e; 
            }
        }

        $keys = implode(", ", array_keys($data));
        $values = array_values($data);
        $placeholders = implode(", ", array_fill(0, count($values), "?"));

        $sql = "INSERT INTO {$this->table} ($keys) VALUES ($placeholders)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new Exception("Error en Insert: " . $e->getMessage());
        }
    }

    /**
     * UPDATE: Actualizar registro usando la PK dinámica
     * Corrige el error 500 al mapear correctamente id_plan/id_perfil
     */
    public function update($id, $data) {
        try {
            $fields = array_map(fn($key) => "$key = :$key", array_keys($data));
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :pk_id";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(":pk_id", $id);
            
            return $stmt->execute();
            
        } catch (\PDOException $e) {
            error_log("Error SQL en {$this->table}: " . $e->getMessage());
            throw new Exception("Fallo al actualizar: " . $e->getMessage());
        }
    }

    /**
     * DELETE: Eliminar un registro usando la PK dinámica
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE {$this->primaryKey} = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            throw new Exception("Error al eliminar: " . $e->getMessage());
        }
    }

    /**
     * MIGRATION: Crear tabla si no existe
     */
    public function createTable(array $fields): void {
        $primaryKeyDef = "{$this->primaryKey} INT AUTO_INCREMENT PRIMARY KEY";
        $columns = [$primaryKeyDef];

        foreach ($fields as $name => $definition) {
            $columns[] = "$name $definition";
        }

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $this->table,
            implode(', ', $columns)
        );

        $this->db->prepare($sql)->execute();
    }
}
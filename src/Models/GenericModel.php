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

        // ✅ MAPA DE LLAVES PRIMARIAS ACTUALIZADO
        $pkMap = [
            'usuarios'     => 'id_usuario',
            'planes'       => 'id_plan',
            'perfiles'     => 'id_perfil',
            'modulos'      => 'id_modulo',
            'tipo_empresa' => 'id_config',
            'item'         => 'id_detalle',
            'empresas'     => 'id_empresa',
            'personal_sst' => 'id_personal_sst', // Confirmado con guion bajo
        ];

        $this->primaryKey = $pkMap[$this->table] ?? 'id';
    }

    /**
     * READ: Obtener todos los registros
     */
    public function all() {
        // Protegemos el nombre de la tabla con backticks
        $query = "SELECT * FROM `{$this->table}`"; 
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ: Obtener un registro por ID
     */
    public function find($id) {
        // Protegemos tabla y columna de llave primaria
        $query = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * CREATE: Insertar registro(s) con soporte para inserción masiva
     */
    public function create($data) {
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

        // ✅ PROTECCIÓN TOTAL: backticks para cada nombre de columna
        $fields = array_keys($data);
        $keys = implode("`, `", $fields);
        $placeholders = implode(", ", array_fill(0, count($data), "?"));

        $sql = "INSERT INTO `{$this->table}` (`$keys`) VALUES ($placeholders)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Reporte de error con contexto de tabla
            throw new Exception("Error en Insert en {$this->table}: " . $e->getMessage());
        }
    }

    /**
     * UPDATE: Actualizar registro usando la PK dinámica
     */
    public function update($id, $data) {
        try {
            if (empty($data) || !is_array($data)) {
                throw new Exception("Datos vacíos para actualizar.");
            }

            // ✅ PROTECCIÓN TOTAL: backticks para tabla y campos
            $fields = array_map(fn($key) => "`$key` = :$key", array_keys($data));
            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE `{$this->primaryKey}` = :pk_id";

            $stmt = $this->db->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(":pk_id", $id);

            return $stmt->execute();

        } catch (\PDOException $e) {
            error_log("Error SQL en {$this->table}: " . $e->getMessage());
            throw new Exception("Fallo al actualizar registro en {$this->table}: " . $e->getMessage());
        }
    }

    /**
     * DELETE: Eliminar un registro usando la PK dinámica
     */
    public function delete($id) {
        $query = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            throw new Exception("Error al eliminar en {$this->table}: " . $e->getMessage());
        }
    }

    /**
     * MIGRATION: Crear tabla si no existe
     */
    public function createTable(array $fields): void {
        $primaryKeyDef = "`{$this->primaryKey}` INT AUTO_INCREMENT PRIMARY KEY";
        $columns = [$primaryKeyDef];

        foreach ($fields as $name => $definition) {
            $columns[] = "`$name` $definition";
        }

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $this->table,
            implode(', ', $columns)
        );

        $this->db->prepare($sql)->execute();
    }
}
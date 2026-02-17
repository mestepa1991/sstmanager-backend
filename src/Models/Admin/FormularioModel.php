<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
use PDO;

class FormularioModel extends GenericModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'formularios');
    }

    public function listAll(): array
    {
        $sql = "SELECT id_formulario, nombre, tipo_norma, estado
                FROM {$this->table}
                ORDER BY id_formulario DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT id_formulario, nombre, tipo_norma, estado
                FROM {$this->table}
                WHERE id_formulario = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createOne(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (nombre, tipo_norma, estado)
                VALUES (:nombre, :tipo_norma, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":nombre"     => $data["nombre"],
            ":tipo_norma" => $data["tipo_norma"],
            ":estado"     => (int)$data["estado"],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateOne(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    tipo_norma = :tipo_norma,
                    estado = :estado
                WHERE id_formulario = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre"     => $data["nombre"],
            ":tipo_norma" => $data["tipo_norma"],
            ":estado"     => (int)$data["estado"],
            ":id"         => $id
        ]);
    }
}

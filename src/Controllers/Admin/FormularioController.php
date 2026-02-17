<?php
namespace App\Controllers\Admin;

use App\Models\Admin\FormularioModel;
use App\Serializers\Admin\FormularioSerializer as S;
use PDO;

class FormularioController
{
    private FormularioModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new FormularioModel($db);
    }

    // GET index.php?table=formularios
    public function index(): void
    {
        $rows = $this->model->listAll();
        S::ok($rows, 200);
    }

    // POST index.php?table=formularios
    public function store(): void
    {
        $body = S::readJsonBody();

        $nombre = trim((string)($body["nombre"] ?? ""));
        $tipo   = trim((string)($body["tipo_norma"] ?? ""));
        $estado = S::toInt01($body["estado"] ?? 1);

        $allowed = ["Guía RUC", "Resolución 0312 / 1072"];

        if ($nombre === "") S::error("El nombre es obligatorio.", 422);
        if ($tipo === "" || !in_array($tipo, $allowed, true)) {
            S::error("Tipo de norma inválido.", 422, ["allowed" => $allowed]);
        }

        $id = $this->model->createOne([
            "nombre"     => $nombre,
            "tipo_norma" => $tipo,
            "estado"     => $estado
        ]);

        $row = $this->model->findById($id);
        S::ok($row, 201);
    }

    // POST index.php?table=formularios&action=update&id=1
    public function update(int $id): void
    {
        $body = S::readJsonBody();

        $nombre = trim((string)($body["nombre"] ?? ""));
        $tipo   = trim((string)($body["tipo_norma"] ?? ""));
        $estado = S::toInt01($body["estado"] ?? 1);

        $allowed = ["Guía RUC", "Resolución 0312 / 1072"];

        if ($id <= 0) S::error("ID inválido.", 400);
        if ($nombre === "") S::error("El nombre es obligatorio.", 422);
        if ($tipo === "" || !in_array($tipo, $allowed, true)) {
            S::error("Tipo de norma inválido.", 422, ["allowed" => $allowed]);
        }

        $exists = $this->model->findById($id);
        if (!$exists) S::error("Formulario no encontrado.", 404);

        $this->model->updateOne($id, [
            "nombre"     => $nombre,
            "tipo_norma" => $tipo,
            "estado"     => $estado
        ]);

        $row = $this->model->findById($id);
        S::ok($row, 200);
    }
}

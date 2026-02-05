<?php
namespace App\Models\Admin;

use App\Models\GenericModel;
use PDO;

class CalificacionModel extends GenericModel {

    public function __construct($db) {
        parent::__construct($db, 'calificaciones');
    }

    public function install() {
        $sqlPadre = "CREATE TABLE IF NOT EXISTS calificaciones (
            id_calificacion INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            estado VARCHAR(20) DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        $this->db->exec($sqlPadre);

        $sqlHija = "CREATE TABLE IF NOT EXISTS calificaciones_detalles (
            id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
            id_calificacion INT(11) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            estado VARCHAR(20) DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        $this->db->exec($sqlHija);

        $this->checkAndAddForeignKey(
            'fk_detalle_calificacion',
            'id_calificacion',
            'calificaciones',
            'id_calificacion'
        );
    }

    private function checkAndAddForeignKey($fkName, $column, $refTable, $refColumn) {
        $sqlCheck = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_NAME = '$fkName'
                     AND TABLE_SCHEMA = DATABASE()";

        if (!$this->db->query($sqlCheck)->fetchColumn()) {
            try {
                $sql = "ALTER TABLE calificaciones_detalles
                        ADD CONSTRAINT $fkName
                        FOREIGN KEY ($column) REFERENCES $refTable($refColumn)
                        ON DELETE CASCADE ON UPDATE CASCADE";
                $this->db->exec($sql);
            } catch (\Exception $e) {}
        }
    }

    // ============================
    // Crear Maestro + (opcional) Detalles
    // ============================
    public function createWithDetails(string $nombre, string $estadoMaestro = 'Activo', array $items = []): int {
        $this->db->beginTransaction();
        try {
            // Maestro
            $stmt = $this->db->prepare("INSERT INTO calificaciones (nombre, estado) VALUES (?, ?)");
            $stmt->execute([$nombre, $estadoMaestro]);

            $id = (int)$this->db->lastInsertId();

            // Detalles opcionales
            if (!empty($items)) {
                $stmtDet = $this->db->prepare("
                    INSERT INTO calificaciones_detalles (id_calificacion, descripcion, valor, estado)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($items as $it) {
                    $desc = trim($it['descripcion'] ?? '');
                    if ($desc === '') continue;

                    $valor = $it['valor'] ?? 0;
                    if (!is_numeric($valor)) $valor = 0;

                    $estadoDet = $it['estado'] ?? 'Activo';

                    $stmtDet->execute([$id, $desc, (float)$valor, $estadoDet]);
                }
            }

            $this->db->commit();
            return $id;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAllActivas(): array {
        $stmt = $this->db->prepare("SELECT * FROM calificaciones WHERE estado = 'Activo' ORDER BY id_calificacion DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetallesActivos(int $id): array {
        $stmt = $this->db->prepare("
            SELECT * FROM calificaciones_detalles
            WHERE id_calificacion = ? AND estado = 'Activo'
            ORDER BY valor DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

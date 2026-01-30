<?php
namespace App\Models\Auth;

use App\Models\GenericModel;

class UsuarioModel extends GenericModel {
    
    public function __construct($db) {
        parent::__construct($db, 'usuarios');
    }

    public function install() {
        // 1. Creación Base
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            tipo_documento VARCHAR(10) NOT NULL DEFAULT 'CC',
            numero_documento VARCHAR(20) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            rol ENUM('Master', 'Administrador', 'Usuario', 'Soporte') NOT NULL DEFAULT 'Usuario',
            id_empresa INT(11) NULL, 
            id_perfil INT(11)  NULL,
            estado TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);

        // 2. Columnas Faltantes (Aquí usamos la función que faltaba)
        $this->checkAndAddColumn('email', "VARCHAR(150) NOT NULL UNIQUE AFTER apellido");
        $this->checkAndAddColumn('tipo_documento', "VARCHAR(10) NOT NULL DEFAULT 'CC' AFTER email");
        $this->checkAndAddColumn('numero_documento', "VARCHAR(20) NOT NULL UNIQUE AFTER tipo_documento");

        // 3. Validar Llaves Foráneas
        $this->checkAndAddForeignKey('fk_usuario_perfil', 'id_perfil', 'perfiles', 'id_perfil');
        $this->checkAndAddForeignKey('fk_usuario_empresa', 'id_empresa', 'empresas', 'id_empresa');

        // 4. Seed
        $this->seedUsuarioMaster();
    }

    /**
     * Revisa si existe una columna, si no, la agrega.
     * (Esta era la función que faltaba)
     */
    private function checkAndAddColumn($columnName, $definition) {
        $stmt = $this->db->query("DESCRIBE usuarios");
        $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        if (!in_array($columnName, $columns)) {
            try {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN $columnName $definition");
                echo "      ➡️ Columna '$columnName' agregada.\n";
            } catch (\Exception $e) {
                // Si falla, probablemente la tabla ya tiene datos incompatibles o bloqueo
                echo "      ⚠️ Error al agregar columna '$columnName': " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Revisa si existe una llave foránea, si no, la crea.
     */
    private function checkAndAddForeignKey($fkName, $column, $refTable, $refColumn) {
        $sqlCheck = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                     WHERE CONSTRAINT_NAME = '$fkName' 
                     AND TABLE_SCHEMA = DATABASE()";
        
        $exists = $this->db->query($sqlCheck)->fetchColumn();

        if (!$exists) {
            try {
                $sql = "ALTER TABLE usuarios 
                        ADD CONSTRAINT $fkName 
                        FOREIGN KEY ($column) REFERENCES $refTable($refColumn) 
                        ON DELETE RESTRICT ON UPDATE CASCADE";
                $this->db->exec($sql);
                echo "      🔗 Llave foránea '$fkName' creada.\n";
            } catch (\Exception $e) {
                echo "      ⚠️ No se pudo crear FK '$fkName'. Verifica que la tabla '$refTable' exista.\n";
            }
        }
    }

    public function validarUsuario($email, $password) {
        // JOIN para traer el nombre del perfil
        $sql = "SELECT u.*, p.nombre_perfil 
                FROM usuarios u 
                INNER JOIN perfiles p ON u.id_perfil = p.id_perfil 
                WHERE u.email = :email AND u.estado = 1 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    private function seedUsuarioMaster() {
        $email = 'admin@sst.com';
        $check = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->fetchColumn() == 0) {
            $passHash = password_hash('123456', PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, apellido, email, tipo_documento, numero_documento, password, id_perfil, rol) 
                    VALUES ('Miguel', 'Estepa', ?, 'CC', '123456789', ?, 1, 'Master')";
            $this->db->prepare($sql)->execute([$email, $passHash]);
            echo "      ✅ Usuario Master creado.\n";
        }
    }
}
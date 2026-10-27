<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Users
{
    private static $columnasPermitidas = [
        'username',
        'password_hash',
        'id_docente',
        'id_estudiante',
        'rol',
    ];

    public static function all()
    {
        return ConexionPDO::query(
            "SELECT u.id, u.username, u.id_docente, u.id_estudiante, u.rol,
                    CONCAT(d.nombre, ' ', d.apellido) AS docente_nombre
             FROM USUARIOS u
             LEFT JOIN DOCENTES d ON d.id = u.id_docente
             ORDER BY u.id DESC"
        );
    }

    public static function find($id)
    {
        $sql = "SELECT u.id, u.username, u.id_docente, u.id_estudiante, u.rol,
                       CONCAT(d.nombre, ' ', d.apellido) AS docente_nombre
                FROM USUARIOS u
                LEFT JOIN DOCENTES d ON d.id = u.id_docente
                WHERE u.id=:id";
        $result = ConexionPDO::query($sql, [':id' => (int) $id]);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function authenticate($username, $password)
    {
        $sql = "SELECT id, username, password_hash, id_docente, id_estudiante, rol FROM USUARIOS WHERE username=:username";
        $result = ConexionPDO::query($sql, [':username' => trim($username)]);
        if (count($result) === 0) {
            return null;
        }

        $user = $result[0];
        $storedPassword = (string) $user['password_hash'];
        $isValid = password_verify($password, $storedPassword);

        if (!$isValid && hash_equals($storedPassword, $password)) {
            $isValid = true;
            self::rehashPassword($user['id'], $password);
        } elseif ($isValid && password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            self::rehashPassword($user['id'], $password);
        }

        if (!$isValid) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    public static function hasAttendanceRecords($id)
    {
        return count(ConexionPDO::query(
            "SELECT id FROM ASISTENCIAS WHERE cod_usuario_registro=:id LIMIT 1",
            [':id' => (int) $id]
        )) > 0;
    }

    public static function updatePasswordHash($id, $passwordHash)
    {
        return ConexionPDO::execute(
            "UPDATE USUARIOS SET password_hash=:password_hash WHERE id=:id",
            [':password_hash' => $passwordHash, ':id' => (int) $id]
        );
    }

    private static function rehashPassword($id, $password)
    {
        if (self::updatePasswordHash($id, password_hash($password, PASSWORD_DEFAULT)) < 1) {
            throw new RuntimeException('No se pudo actualizar el hash de la contraseña.');
        }
    }

    public static function update($id, $data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        $data = self::prepararDatos($data);

        if (count($data) === 0) {
            return 0;
        }

        $campos = [];
        $valores = [];
        foreach ($data as $columna => $valor) {
            $campos[] = "$columna=:$columna";
            $valores[":$columna"] = $valor;
        }

        $sql = "UPDATE USUARIOS SET " . implode(',', $campos) . " WHERE id=:id";
        $valores[':id'] = (int) $id;
        return ConexionPDO::execute($sql, $valores);
    }

    public static function add($data)
    {
        $data = self::prepararDatos($data);
        if (count($data) === 0) {
            return 0;
        }

        $campos = [];
        $placeholders = [];
        $valores = [];
        foreach ($data as $columna => $valor) {
            $campos[] = $columna;
            $placeholders[] = ":$columna";
            $valores[":$columna"] = $valor;
        }

        $sql = "INSERT INTO USUARIOS (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM USUARIOS WHERE id=:id";
        $valores = [":id" => $id];
        return ConexionPDO::execute($sql, $valores);
    }

    private static function prepararDatos($data)
    {
        $datos = [];
        foreach ($data as $columna => $valor) {
            if (in_array($columna, self::$columnasPermitidas, true)) {
                $datos[$columna] = is_string($valor) ? trim($valor) : $valor;
            }
        }
        return $datos;
    }
}

<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Docentes
{
    private static $columnasPermitidas = [
        'nombre',
        'apellido',
        'telefono',
    ];

    public static function all()
    {
        return ConexionPDO::query("SELECT id, nombre, apellido, telefono FROM DOCENTES ORDER BY id DESC");
    }

    public static function find($id)
    {
        $sql = "SELECT id, nombre, apellido, telefono FROM DOCENTES WHERE id=:id";
        $result = ConexionPDO::query($sql, [':id' => (int) $id]);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function existsDuplicate($data, $excludeId = null)
    {
        $sql = "SELECT id FROM DOCENTES
                WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))
                  AND LOWER(TRIM(apellido)) = LOWER(TRIM(:apellido))
                  AND COALESCE(NULLIF(TRIM(telefono), ''), '') = :telefono";
        $params = [
            ':nombre' => trim((string) $data['nombre']),
            ':apellido' => trim((string) $data['apellido']),
            ':telefono' => trim((string) ($data['telefono'] ?? '')),
        ];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = (int) $excludeId;
        }

        $sql .= " LIMIT 1";
        return count(ConexionPDO::query($sql, $params)) > 0;
    }

    public static function hasRelatedData($id)
    {
        return count(ConexionPDO::query(
            "SELECT 1 FROM ASIGNACIONES WHERE cod_docente=:id
             UNION ALL SELECT 1 FROM USUARIOS WHERE id_docente=:user_teacher_id LIMIT 1",
            [':id' => (int) $id, ':user_teacher_id' => (int) $id]
        )) > 0;
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

        $sql = "UPDATE DOCENTES SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO DOCENTES (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM DOCENTES WHERE id=:id";
        $valores = [":id" => (int) $id];
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

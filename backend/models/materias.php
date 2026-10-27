<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Materias
{
    private static $columnasPermitidas = [
        'nombre_materia',
    ];

    public static function all($teacherId = null)
    {
        $sql = "SELECT DISTINCT m.id, m.nombre_materia FROM MATERIAS m";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_materia=m.id WHERE a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $sql .= " ORDER BY m.id DESC";
        return ConexionPDO::query($sql, $params);
    }

    public static function find($id, $teacherId = null)
    {
        $sql = "SELECT DISTINCT m.id, m.nombre_materia FROM MATERIAS m";
        $params = [':id' => (int) $id];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_materia=m.id
                      WHERE m.id=:id AND a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        } else {
            $sql .= " WHERE m.id=:id";
        }
        $result = ConexionPDO::query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function existsDuplicate($data, $excludeId = null)
    {
        $sql = "SELECT id FROM MATERIAS WHERE LOWER(TRIM(nombre_materia))=LOWER(TRIM(:nombre))";
        $params = [':nombre' => trim((string) ($data['nombre_materia'] ?? ''))];
        if ($excludeId !== null) {
            $sql .= " AND id<>:id";
            $params[':id'] = (int) $excludeId;
        }
        return count(ConexionPDO::query($sql . " LIMIT 1", $params)) > 0;
    }

    public static function hasAssignments($id)
    {
        return count(ConexionPDO::query(
            "SELECT id FROM ASIGNACIONES WHERE cod_materia=:id LIMIT 1",
            [':id' => (int) $id]
        )) > 0;
    }

    public static function update($id, $data)
    {
        unset($data['id']);
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

        $sql = "UPDATE MATERIAS SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO MATERIAS (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        return ConexionPDO::execute("DELETE FROM MATERIAS WHERE id=:id", [':id' => (int) $id]);
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

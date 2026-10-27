<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Inscripciones
{
    private static $columnasPermitidas = [
        'cod_estudiante',
        'cod_curso',
        'gestion',
    ];

    public static function all($teacherId = null)
    {
        $sql = "SELECT DISTINCT i.id, i.cod_estudiante, i.cod_curso, i.gestion FROM INSCRIPCIONES i";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_curso=i.cod_curso WHERE a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $sql .= " ORDER BY i.id DESC";
        return ConexionPDO::query($sql, $params);
    }

    public static function find($id, $teacherId = null)
    {
        $sql = "SELECT DISTINCT i.id, i.cod_estudiante, i.cod_curso, i.gestion FROM INSCRIPCIONES i";
        $params = [':id' => (int) $id];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_curso=i.cod_curso
                      WHERE i.id=:id AND a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        } else {
            $sql .= " WHERE i.id=:id";
        }
        $result = ConexionPDO::query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function courseBelongsToTeacher($courseId, $teacherId)
    {
        return count(ConexionPDO::query(
            "SELECT id FROM ASIGNACIONES WHERE cod_curso=:course_id AND cod_docente=:teacher_id LIMIT 1",
            [':course_id' => (int) $courseId, ':teacher_id' => (int) $teacherId]
        )) > 0;
    }

    public static function existsStudentYear($studentId, $year, $excludeId = null)
    {
        $sql = "SELECT id FROM INSCRIPCIONES WHERE cod_estudiante=:student_id AND gestion=:year";
        $params = [':student_id' => (int) $studentId, ':year' => (int) $year];
        if ($excludeId !== null) {
            $sql .= " AND id<>:id";
            $params[':id'] = (int) $excludeId;
        }
        return count(ConexionPDO::query($sql . " LIMIT 1", $params)) > 0;
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

        $sql = "UPDATE INSCRIPCIONES SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO INSCRIPCIONES (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        return ConexionPDO::execute("DELETE FROM INSCRIPCIONES WHERE id=:id", [':id' => (int) $id]);
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

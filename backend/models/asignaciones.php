<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Asignaciones
{
     private static $columnasPermitidas = [
        'cod_docente',
        'cod_materia',
        'cod_curso',
      ];

    public static function all($teacherId = null)
    {
        $sql = "SELECT id, cod_docente, cod_materia, cod_curso FROM ASIGNACIONES";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " WHERE cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $sql .= " ORDER BY id DESC";
        return ConexionPDO::query($sql, $params);
    }

    public static function find($id, $teacherId = null)
    {
        $sql = "SELECT id, cod_docente, cod_materia, cod_curso FROM ASIGNACIONES WHERE id=:id";
        $params = [':id' => (int) $id];
        if ($teacherId !== null) {
            $sql .= " AND cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $result = ConexionPDO::query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function existsCombination($teacherId, $subjectId, $courseId, $excludeId = null)
    {
        $sql = "SELECT id FROM ASIGNACIONES
                WHERE cod_docente=:teacher_id AND cod_materia=:subject_id AND cod_curso=:course_id";
        $params = [
            ':teacher_id' => (int) $teacherId,
            ':subject_id' => (int) $subjectId,
            ':course_id' => (int) $courseId,
        ];
        if ($excludeId !== null) {
            $sql .= " AND id<>:id";
            $params[':id'] = (int) $excludeId;
        }
        return count(ConexionPDO::query($sql . " LIMIT 1", $params)) > 0;
    }

    public static function hasAttendance($id)
    {
        return count(ConexionPDO::query(
            "SELECT id FROM ASISTENCIAS WHERE cod_asignacion=:id LIMIT 1",
            [':id' => (int) $id]
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

        $sql = "UPDATE ASIGNACIONES SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO ASIGNACIONES (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM ASIGNACIONES WHERE id=:id";
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

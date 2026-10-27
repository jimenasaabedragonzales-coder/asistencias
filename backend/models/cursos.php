<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Cursos
{
    private static $columnasPermitidas = [
        'nombre_curso',
        'nivel',
        'paralelo',
    ];

    public static function all($teacherId = null)
    {
        $sql = "SELECT DISTINCT c.id, c.nombre_curso, c.nivel, c.paralelo FROM CURSOS c";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_curso=c.id WHERE a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $sql .= " ORDER BY c.id DESC";
        return ConexionPDO::query($sql, $params);
    }

    public static function find($id, $teacherId = null)
    {
        $sql = "SELECT DISTINCT c.id, c.nombre_curso, c.nivel, c.paralelo FROM CURSOS c";
        $params = [':id' => (int) $id];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES a ON a.cod_curso=c.id
                      WHERE c.id=:id AND a.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        } else {
            $sql .= " WHERE c.id=:id";
        }
        $result = ConexionPDO::query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function existsDuplicate($data, $excludeId = null)
    {
        $sql = "SELECT id FROM CURSOS
                WHERE LOWER(TRIM(nombre_curso))=LOWER(TRIM(:nombre_curso))
                  AND LOWER(TRIM(nivel))=LOWER(TRIM(:nivel))
                  AND LOWER(TRIM(paralelo))=LOWER(TRIM(:paralelo))";
        $params = [
            ':nombre_curso' => trim((string) ($data['nombre_curso'] ?? '')),
            ':nivel' => trim((string) ($data['nivel'] ?? '')),
            ':paralelo' => trim((string) ($data['paralelo'] ?? '')),
        ];
        if ($excludeId !== null) {
            $sql .= " AND id<>:id";
            $params[':id'] = (int) $excludeId;
        }
        return count(ConexionPDO::query($sql . " LIMIT 1", $params)) > 0;
    }

    public static function hasRelations($id)
    {
        return count(ConexionPDO::query(
            "SELECT 1 FROM ASIGNACIONES WHERE cod_curso=:id
             UNION ALL SELECT 1 FROM INSCRIPCIONES WHERE cod_curso=:enrollment_course_id LIMIT 1",
            [':id' => (int) $id, ':enrollment_course_id' => (int) $id]
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

        $sql = "UPDATE CURSOS SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO CURSOS (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM CURSOS WHERE id=:id";
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

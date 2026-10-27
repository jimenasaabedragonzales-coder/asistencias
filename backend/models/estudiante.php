<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Estudiantes
{

    private static $columnasPermitidas = [
        'nombre',
        'apellido',
        'direccion',
        'telefono',
    ];

    public static function all($teacherId = null)
    {
        // El docente necesita consultar el padrón completo para inscribir alumnos
        // en sus cursos; los estudiantes no usan este sistema para iniciar sesión.
        return ConexionPDO::query(
            "SELECT id, nombre, apellido, direccion, telefono FROM ESTUDIANTES ORDER BY id DESC"
        );
    }

    public static function existsDuplicate($data, $excludeId = null)
    {
        $data = self::prepararDatos($data);
        $sql = "SELECT id FROM ESTUDIANTES
                WHERE LOWER(TRIM(nombre))=LOWER(TRIM(:nombre))
                  AND LOWER(TRIM(apellido))=LOWER(TRIM(:apellido))
                  AND LOWER(TRIM(direccion)) <=> LOWER(TRIM(:direccion))
                  AND COALESCE(NULLIF(TRIM(telefono), ''), '')=:telefono";
        $params = [
            ':nombre' => $data['nombre'] ?? '',
            ':apellido' => $data['apellido'] ?? '',
            ':direccion' => $data['direccion'] ?? null,
            ':telefono' => trim((string) ($data['telefono'] ?? '')),
        ];
        if ($excludeId !== null) {
            $sql .= " AND id<>:id";
            $params[':id'] = (int) $excludeId;
        }
        $result = ConexionPDO::query($sql . " LIMIT 1", $params);
        return count($result) > 0;
    }

    public static function hasRelatedData($id)
    {
        return count(ConexionPDO::query(
            "SELECT 1 FROM USUARIOS WHERE id_estudiante=:id
             UNION ALL SELECT 1 FROM INSCRIPCIONES WHERE cod_estudiante=:enrollment_student_id
             UNION ALL SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=:attendance_student_id LIMIT 1",
            [':id' => (int) $id, ':enrollment_student_id' => (int) $id, ':attendance_student_id' => (int) $id]
        )) > 0;
    }

    public static function find($id, $teacherId = null)
    {
        $result = ConexionPDO::query(
            "SELECT id, nombre, apellido, direccion, telefono FROM ESTUDIANTES WHERE id=:id",
            [':id' => (int) $id]
        );
        return count($result) > 0 ? $result[0] : null;
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

        $sql = "UPDATE ESTUDIANTES SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO ESTUDIANTES (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM ESTUDIANTES WHERE id=:id";
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

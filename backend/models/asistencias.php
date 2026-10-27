<?php
include_once __DIR__ . "/../Config/conexionDB.php";
class Asistencias
{
    private static $columnasPermitidas = [
        'cod_estudiante',
        'cod_asignacion',
        'cod_usuario_registro',
        'fecha',
        'estado',
        'observacion',
    ];

    public static function all($teacherId = null)
    {
        $sql = "SELECT a.id, a.cod_estudiante, a.cod_asignacion, a.cod_usuario_registro, a.fecha, a.estado, a.observacion
                FROM ASISTENCIAS a";
        $params = [];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES asignacion ON asignacion.id = a.cod_asignacion
                      WHERE asignacion.cod_docente = :teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        }
        $sql .= " ORDER BY a.fecha DESC, a.id DESC";
        return ConexionPDO::query($sql, $params);
    }

    public static function find($id, $teacherId = null)
    {
        $sql = "SELECT a.id, a.cod_estudiante, a.cod_asignacion, a.cod_usuario_registro, a.fecha, a.estado, a.observacion
                FROM ASISTENCIAS a";
        $params = [':id' => (int) $id];
        if ($teacherId !== null) {
            $sql .= " INNER JOIN ASIGNACIONES asignacion ON asignacion.id = a.cod_asignacion
                      WHERE a.id=:id AND asignacion.cod_docente=:teacher_id";
            $params[':teacher_id'] = (int) $teacherId;
        } else {
            $sql .= " WHERE a.id=:id";
        }
        $result = ConexionPDO::query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public static function assignmentBelongsToTeacher($assignmentId, $teacherId)
    {
        return count(ConexionPDO::query(
            "SELECT id FROM ASIGNACIONES WHERE id=:assignment_id AND cod_docente=:teacher_id",
            [':assignment_id' => (int) $assignmentId, ':teacher_id' => (int) $teacherId]
        )) > 0;
    }

    public static function existsForStudentAssignmentDate($studentId, $assignmentId, $date, $excludeId = null)
    {
        $sql = "SELECT id FROM ASISTENCIAS
                WHERE cod_estudiante=:student_id AND cod_asignacion=:assignment_id AND fecha=:attendance_date";
        $params = [
            ':student_id' => (int) $studentId,
            ':assignment_id' => (int) $assignmentId,
            ':attendance_date' => $date,
        ];
        if ($excludeId !== null) {
            $sql .= " AND id<>:exclude_id";
            $params[':exclude_id'] = (int) $excludeId;
        }
        $sql .= ' LIMIT 1';
        return count(ConexionPDO::query($sql, $params)) > 0;
    }

    public static function studentEnrolledInCourseForDate($studentId, $assignmentId, $date)
    {
        return count(ConexionPDO::query(
            "SELECT i.id
             FROM INSCRIPCIONES i
             INNER JOIN ASIGNACIONES a ON a.cod_curso = i.cod_curso
             WHERE i.cod_estudiante=:student_id
               AND a.id=:assignment_id
               AND i.gestion=YEAR(:attendance_date)",
            [
                ':student_id' => (int) $studentId,
                ':assignment_id' => (int) $assignmentId,
                ':attendance_date' => $date,
            ]
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

        $sql = "UPDATE ASISTENCIAS SET " . implode(',', $campos) . " WHERE id=:id";
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

        $sql = "INSERT INTO ASISTENCIAS (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
        return ConexionPDO::execute($sql, $valores, true);
    }

    public static function delete($id)
    {
        $sql = "DELETE FROM ASISTENCIAS WHERE id=:id";
        $valores = [":id" => $id];
        return ConexionPDO::execute($sql, $valores);
    }

    private static function prepararDatos($data)
    {
        $datos = [];
        foreach ($data as $columna => $valor) {
            if (in_array($columna, self::$columnasPermitidas, true)) {
                if ($columna === 'estado' && $valor === 'Retrasado') {
                    $valor = 'Retraso';
                }
                $datos[$columna] = is_string($valor) ? trim($valor) : $valor;
            }
        }
        return $datos;
    }}

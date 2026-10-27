<?php
require_once __DIR__ . "/../models/asistencias.php";

class AsistenciasController
{
    public function getAll()
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $asistencia = Asistencias::all($teacherId);
        echo json_encode($asistencia); 
    }

    public function getById($id)
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $asistencia = Asistencias::find($id, $teacherId);
        if ($asistencia) {
            echo json_encode($asistencia);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Datos de asistencia no encontrada",
        ]);
    }

    public function update($id)
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error codificacion",
                "message" => json_last_error_msg() ?: "JSON inválido",
            ]);
            return;
        }

        $errores = $this->validarDatos($data);

        if (count($errores) > 0) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Existen errores de validacion",
                "errores" => $errores,
            ]);
            return;
        }

        $user = $_SESSION['user'] ?? [];
        $teacherId = ($user['rol'] ?? null) === 'docente'
            ? (int) ($user['id_docente'] ?? 0)
            : null;
        $existingAttendance = Asistencias::find($id, $teacherId);
        if (!$existingAttendance) {
            http_response_code(404);
            echo json_encode([
                "estado" => false,
                "message" => "Asistencia no encontrada",
            ]);
            return;
        }

        if (Asistencias::existsForStudentAssignmentDate(
            $data['cod_estudiante'],
            $data['cod_asignacion'],
            $data['fecha'],
            $id
        )) {
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "message" => "Ya existe otra asistencia para este estudiante, materia y fecha",
            ]);
            return;
        }

        if ($teacherId !== null) {
            if (!Asistencias::assignmentBelongsToTeacher($data['cod_asignacion'], $teacherId)) {
                http_response_code(403);
                echo json_encode([
                    "estado" => false,
                    "message" => "Solo puedes modificar asistencias de tus asignaciones",
                ]);
                return;
            }
            if (!Asistencias::studentEnrolledInCourseForDate(
                $data['cod_estudiante'],
                $data['cod_asignacion'],
                $data['fecha']
            )) {
                http_response_code(422);
                echo json_encode([
                    "estado" => false,
                    "message" => "El estudiante no está inscrito en el curso asignado para esa gestión",
                ]);
                return;
            }
            $data['cod_usuario_registro'] = (int) $user['id'];
        }

        Asistencias::update($id, $data);
        echo json_encode([
            "estado" => true,
            "message" => "Datos de la asistencia actualizados correctamente",
        ]);
    }

    public function add()
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error codificacion",
                "message" => json_last_error_msg() ?: "JSON inválido",
            ]);
            return;
        }

        $errores = $this->validarDatos($data);

        if (count($errores) > 0) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Existen errores de validacion",
                "errores" => $errores,
            ]);
            return;
        }

        if (Asistencias::existsForStudentAssignmentDate(
            $data['cod_estudiante'],
            $data['cod_asignacion'],
            $data['fecha']
        )) {
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "message" => "Ya existe una asistencia para este estudiante, materia y fecha",
            ]);
            return;
        }

        $user = $_SESSION['user'] ?? [];
        if (($user['rol'] ?? null) === 'docente') {
            $teacherId = (int) ($user['id_docente'] ?? 0);
            if (!Asistencias::assignmentBelongsToTeacher($data['cod_asignacion'], $teacherId)) {
                http_response_code(403);
                echo json_encode([
                    "estado" => false,
                    "message" => "Solo puedes registrar asistencias de tus asignaciones",
                ]);
                return;
            }
            if (!Asistencias::studentEnrolledInCourseForDate(
                $data['cod_estudiante'],
                $data['cod_asignacion'],
                $data['fecha']
            )) {
                http_response_code(422);
                echo json_encode([
                    "estado" => false,
                    "message" => "El estudiante no está inscrito en el curso asignado para esa gestión",
                ]);
                return;
            }
            $data['cod_usuario_registro'] = (int) $user['id'];
        }

        $asistencia = Asistencias::add($data);
        if ($asistencia) {
            echo json_encode([
                "estado" => true,
                "message" => "Datos de la asistencia adicionado correctamente",
            ]);
            return;
        }

        http_response_code(400);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo adicionar los datos de la asistencia",
        ]);
    }

    public function delete($id)
    {
        $user = $_SESSION['user'] ?? [];
        $teacherId = ($user['rol'] ?? null) === 'docente'
            ? (int) ($user['id_docente'] ?? 0)
            : null;
        if (!Asistencias::find($id, $teacherId)) {
            http_response_code(404);
            echo json_encode([
                "estado" => false,
                "message" => "Asistencia no encontrada",
            ]);
            return;
        }

        $asistencia = Asistencias::delete($id);
        if ($asistencia) {
            echo json_encode([
                "estado" => true,
                "message" => "Datos de la asistencia eliminados correctamente",
            ]);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Asistencia no encontrada",
        ]);
    }

    private function validarDatos($data)
    {
        $errores = [];

        if (!is_array($data)) {
            $errores[] = "Los datos enviados no son validos";
            return $errores;
        }

        foreach (['cod_estudiante', 'cod_asignacion'] as $field) {
            if (!isset($data[$field]) || filter_var($data[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $errores[] = "El campo $field debe ser un identificador entero positivo";
            }
        }

        if (isset($data['cod_usuario_registro']) && $data['cod_usuario_registro'] !== null
            && filter_var($data['cod_usuario_registro'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errores[] = "El campo cod_usuario_registro debe ser nulo o un identificador entero positivo";
        }

        $fecha = $data['fecha'] ?? null;
        $fechaValida = is_string($fecha) ? DateTime::createFromFormat('!Y-m-d', $fecha) : false;
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            $errores[] = "El campo fecha debe tener el formato YYYY-MM-DD y ser una fecha válida";
        }

        if (!isset($data['estado']) || trim((string) $data['estado']) === "") {
            $errores[] = "El campo estado es obligatorio";
        } elseif (!in_array($data['estado'], ['Presente', 'Ausente', 'Licencia', 'Retraso'], true)) {
            $errores[] = "El campo estado debe ser Presente, Ausente, Licencia o Retraso";
        }

        if (isset($data['observacion']) && $data['observacion'] !== null) {
            $longitudObservacion = is_string($data['observacion'])
                ? preg_match_all('/./us', $data['observacion'], $coincidencias)
                : false;
            if ($longitudObservacion === false || $longitudObservacion > 250) {
                $errores[] = "El campo observacion debe tener como máximo 250 caracteres";
            }
        }

        return $errores;
    }
}

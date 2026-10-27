<?php
require_once __DIR__ . "/../models/inscripciones.php";
class InscripcionesController{
    public function getAll()
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $inscripciones = Inscripciones::all($teacherId);
        echo json_encode($inscripciones);
    }

    public function getById($id)
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $inscripcion = Inscripciones::find($id, $teacherId);
        if ($inscripcion) {
            echo json_encode($inscripcion);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Inscripcion no encontrada",
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

        if (Inscripciones::existsStudentYear($data['cod_estudiante'], $data['gestion'], $id)) {
            http_response_code(409);
            echo json_encode(["estado" => false, "message" => "El estudiante ya tiene una inscripción para esa gestión."]);
            return;
        }

        $inscripcion = Inscripciones::update($id, $data);
        if ($inscripcion) {
            echo json_encode(["estado" => true, "message" => "Inscripcion actualizada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo actualizar la inscripcion"]);
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
            echo json_encode(["status" => "error", "message" => "Existen errores de validacion", "errores" => $errores]);
            return;
        }

        if (($_SESSION['user']['rol'] ?? null) === 'docente'
            && !Inscripciones::courseBelongsToTeacher(
                $data['cod_curso'],
                (int) $_SESSION['user']['id_docente']
            )) {
            http_response_code(403);
            echo json_encode([
                "estado" => false,
                "message" => "Solo puedes inscribir estudiantes en tus cursos",
            ]);
            return;
        }

        try {
            $inscripcion = Inscripciones::add($data);
        } catch (PDOException $error) {
            $errorInfo = $error->errorInfo ?? [];
            if ($error->getCode() === '23000' && ($errorInfo[1] ?? null) === 1062) {
                http_response_code(409);
                echo json_encode([
                    "estado" => false,
                    "message" => "El estudiante ya está inscrito en la gestión seleccionada",
                ]);
                return;
            }

            throw $error;
        }

        if ($inscripcion) {
            echo json_encode(["estado" => true, "message" => "Inscripcion adicionada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo adicionar la inscripcion"]);
    }

    public function delete($id)
    {
        $inscripcion = Inscripciones::delete($id);
        if ($inscripcion) {
            echo json_encode(["estado" => true, "message" => "Inscripcion eliminada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo eliminar la inscripcion"]);
    }

    private function validarDatos($data)
    {
        $errores = [];
        if (!is_array($data)) {
            $errores[] = "Los datos enviados no son validos";
            return $errores;
        }

        foreach (['cod_estudiante', 'cod_curso'] as $campo) {
            if (!isset($data[$campo]) || filter_var($data[$campo], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $errores[] = "El campo $campo debe ser un identificador entero positivo";
            }
        }

        if (!isset($data['gestion']) || filter_var($data['gestion'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1901, 'max_range' => 2155]]) === false) {
            $errores[] = "El campo gestion debe ser un año válido";
        }

        return $errores;
    }
}

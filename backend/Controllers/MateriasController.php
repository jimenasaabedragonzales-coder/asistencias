<?php
require_once __DIR__ . "/../models/materias.php";
class MateriasController{
    public function getAll()
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $materias = Materias::all($teacherId);
        echo json_encode($materias);
    }

    public function getById($id)
    {
        $teacherId = ($_SESSION['user']['rol'] ?? null) === 'docente'
            ? (int) $_SESSION['user']['id_docente']
            : null;
        $materia = Materias::find($id, $teacherId);
        if ($materia) {
            echo json_encode($materia);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Materia no encontrada",
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
            echo json_encode(["status" => "error", "message" => "Existen errores de validacion", "errores" => $errores]);
            return;
        }

        if (Materias::existsDuplicate($data, $id)) {
            http_response_code(409);
            echo json_encode(["estado" => false, "message" => "Ya existe una materia con ese nombre."]);
            return;
        }

        $materia = Materias::update($id, $data);
        if ($materia) {
            echo json_encode(["estado" => true, "message" => "Materia actualizada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo actualizar la materia"]);
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

        if (Materias::existsDuplicate($data)) {
            http_response_code(409);
            echo json_encode(["estado" => false, "message" => "Ya existe una materia con ese nombre."]);
            return;
        }

        $materia = Materias::add($data);
        if ($materia) {
            echo json_encode(["estado" => true, "message" => "Materia adicionada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo adicionar la materia"]);
    }

    public function delete($id)
    {
        if (!Materias::find($id)) {
            http_response_code(404);
            echo json_encode(["estado" => false, "message" => "Materia no encontrada."]);
            return;
        }
        if (Materias::hasAssignments($id)) {
            http_response_code(409);
            echo json_encode(["estado" => false, "message" => "No se puede eliminar esta materia porque está incluida en asignaciones docentes y puede tener asistencias registradas."]);
            return;
        }
        $materia = Materias::delete($id);
        if ($materia) {
            echo json_encode(["estado" => true, "message" => "Materia eliminada correctamente"]);
            return;
        }

        http_response_code(400);
        echo json_encode(["estado" => false, "message" => "No se pudo eliminar la materia"]);
    }

    private function validarDatos($data)
    {
        $errores = [];

        if (!is_array($data)) {
            $errores[] = "Los datos enviados no son validos";
            return $errores;
        }

        if (!isset($data['nombre_materia']) || trim((string) $data['nombre_materia']) === '') {
            $errores[] = "El campo nombre_materia es obligatorio";
        } elseif (strlen((string) $data['nombre_materia']) > 100) {
            $errores[] = "El nombre de la materia no debe superar 100 caracteres";
        }

        return $errores;
    }
}

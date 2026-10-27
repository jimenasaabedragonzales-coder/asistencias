<?php
require_once __DIR__ . "/../models/docentes.php";
class DocentesController
{
    public function getAll()
    {
        $docente = Docentes::all();
        echo json_encode($docente); 
    }

    public function getById($id)
    {
        $docente = Docentes::find($id);
        if ($docente) {
            echo json_encode($docente);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Docente no encontrado",
        ]);
    }

    public function update($id)
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) 
            {
            http_response_code(400);
            echo json_encode([
                "status" => "error codificacion",
                "message" => json_last_error_msg() ?: "JSON inválido",
            ]);
            return;
        }

        $errores = $this->validarDatos($data, false);

        if (count($errores) > 0) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Existen errores de validacion",
                "errores" => $errores,
            ]);
            return;
        }

        $actual = Docentes::find($id);
        if (!$actual) {
            http_response_code(404);
            echo json_encode([
                "estado" => false,
                "message" => "Docente no encontrado",
            ]);
            return;
        }

        if (Docentes::existsDuplicate(array_merge($actual, $data), $id)) {
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "message" => "Ya existe un docente con el mismo nombre, apellido y teléfono",
            ]);
            return;
        }

        Docentes::update($id, $data);
        echo json_encode([
            "estado" => true,
            "message" => "Datos del docente actualizados correctamente",
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

        $errores = $this->validarDatos($data, true);

        if (count($errores) > 0) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "Existen errores de validacion",
                "errores" => $errores,
            ]);
            return;
        }

        if (Docentes::existsDuplicate($data)) {
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "message" => "Ya existe un docente con el mismo nombre, apellido y teléfono",
            ]);
            return;
        }

        $docente = Docentes::add($data);
        if ($docente) {
            echo json_encode([
                "estado" => true,
                "message" => "Datos del docente adicionado correctamente",
                "id" => (int) $docente,
            ]);
            return;
        }

        http_response_code(400);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo adicionar los datos del docente",
        ]);
    }

    public function delete($id)
    {
        if (!Docentes::find($id)) {
            http_response_code(404);
            echo json_encode([
                "estado" => false,
                "message" => "Docente no encontrado",
            ]);
            return;
        }

        if (Docentes::hasRelatedData($id)) {
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "message" => "No se puede eliminar este docente porque tiene materias/cursos asignados o una cuenta de acceso vinculada.",
            ]);
            return;
        }

        $docente = Docentes::delete($id);
        if ($docente) {
            echo json_encode([
                "estado" => true,
                "message" => "Datos del docente eliminados correctamente",
            ]);
            return;
        }

        http_response_code(409);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo eliminar el docente porque tiene registros relacionados.",
        ]);
    }

    private function validarDatos($data, $esNuevo = false)
    {
        $errores = [];

        if (!is_array($data)) {
            $errores[] = "Los datos enviados no son validos";
            return $errores;
        }

        if (!isset($data['nombre']) || !is_scalar($data['nombre']) || trim((string) $data['nombre']) === "") {
            $errores[] = "El campo nombre es obligatorio";
        } elseif (strlen((string) $data['nombre']) > 50) {
            $errores[] = "El campo nombre no debe superar los 50 caracteres";
        }

        if ($esNuevo && (!isset($data['apellido']) || !is_scalar($data['apellido']) || trim((string) $data['apellido']) === "")) {
            $errores[] = "El campo apellido es obligatorio";
        } elseif (isset($data['apellido']) && (!is_scalar($data['apellido']) || strlen((string) $data['apellido']) > 50)) {
            $errores[] = "El campo apellido no debe superar los 50 caracteres";
        }

        if (isset($data['telefono']) && (!is_scalar($data['telefono']) || strlen((string) $data['telefono']) > 15)) {
            $errores[] = "El campo telefono no debe superar los 15 caracteres";
        }

        return $errores;
    }
}

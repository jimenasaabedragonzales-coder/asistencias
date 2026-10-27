<?php
require_once __DIR__ . "/../models/Users.php";

class UserController
{
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "estado" => false,
                "message" => "El cuerpo de la solicitud debe ser JSON válido",
            ]);
            return;
        }

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        if (!is_string($username) || !is_string($password) || trim($username) === '' || $password === '') {
            http_response_code(422);
            echo json_encode([
                "estado" => false,
                "message" => "El usuario y la contraseña son obligatorios",
            ]);
            return;
        }
        $username = trim($username);

        $user = Users::authenticate($username, $password);
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                "estado" => false,
                "message" => "Usuario o contraseña incorrectos",
            ]);
            return;
        }

        if (!in_array($user['rol'] ?? null, ['admin', 'docente'], true)) {
            http_response_code(403);
            echo json_encode([
                "estado" => false,
                "message" => "Solo las cuentas de administrador y docente pueden iniciar sesión",
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'id_docente' => $user['id_docente'] !== null ? (int) $user['id_docente'] : null,
            'id_estudiante' => $user['id_estudiante'] !== null ? (int) $user['id_estudiante'] : null,
            'rol' => $user['rol'],
        ];

        echo json_encode([
            "estado" => true,
            "usuario" => [
                "id" => (int) $user['id'],
                "username" => $user['username'],
                "id_docente" => $user['id_docente'] !== null ? (int) $user['id_docente'] : null,
                "id_estudiante" => $user['id_estudiante'] !== null ? (int) $user['id_estudiante'] : null,
                "rol" => $user['rol'],
            ],
        ]);
    }

    public function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        echo json_encode(["estado" => true, "message" => "Sesión cerrada correctamente"]);
    }

    public function getAll()
    {
        $user = Users::all();
        echo json_encode($user); 
    }

    public function getById($id)
    {
        $user = Users::find($id);
        if ($user) {
            echo json_encode($user);
            return;
        }

        http_response_code(404);
        echo json_encode([
            "estado" => false,
            "message" => "Usuario no encontrado",
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

        $data = $this->normalizarContrasena($data);
        $user = Users::update($id, $data);
        if ($user) {
            echo json_encode([
                "estado" => true,
                "message" => "Usuario actualizado correctamente",
            ]);
            return;
        }

        http_response_code(400);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo actualizar el usuario",
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

        $data = $this->normalizarRol($data, true);
        $data = $this->normalizarContrasena($data);
        $user = Users::add($data);
        if ($user) {
            echo json_encode([
                "estado" => true,
                "message" => "Usuario adicionado correctamente",
            ]);
            return;
        }

        http_response_code(400);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo crear el usuario",
        ]);
    }

    public function delete($id)
    {
        if (!Users::find($id)) {
            http_response_code(404);
            echo json_encode(["estado" => false, "message" => "Usuario no encontrado."]);
            return;
        }
        if (Users::hasAttendanceRecords($id)) {
            http_response_code(409);
            echo json_encode(["estado" => false, "message" => "No se puede eliminar esta cuenta porque aparece como responsable de asistencias registradas. El historial debe conservarse."]);
            return;
        }
        $user = Users::delete($id);
        if ($user) {
            echo json_encode([
                "estado" => true,
                "message" => "Usuario eliminado correctamente",
            ]);
            return;
        }

        http_response_code(400);
        echo json_encode([
            "estado" => false,
            "message" => "No se pudo eliminar el usuario",
        ]);
    }

    private function validarDatos($data, $esNuevo = false)
    {
        $errores = [];

        if (!is_array($data)) {
            $errores[] = "Los datos enviados no son validos";
            return $errores;
        }

        if (!isset($data['username']) || !is_string($data['username']) || trim($data['username']) === "") {
            $errores[] = "El campo username es obligatorio";
        } elseif (strlen($data['username']) > 50) {
            $errores[] = "El campo username no debe superar los 50 caracteres";
        }

        $passwordValue = $data['password'] ?? $data['password_hash'] ?? null;
        if ($esNuevo && (!is_string($passwordValue) || trim($passwordValue) === "")) {
            $errores[] = "El campo password es obligatorio";
        }
        if (isset($data['password']) && !is_string($data['password'])) {
            $errores[] = "El campo password debe ser texto";
        } elseif (isset($data['password']) && strlen($data['password']) > 72) {
            $errores[] = "La contraseña no debe superar los 72 bytes";
        } elseif (isset($data['password_hash']) && !is_string($data['password_hash'])) {
            $errores[] = "El campo password_hash debe ser texto";
        } elseif (isset($data['password_hash']) && strlen($data['password_hash']) > 255) {
            $errores[] = "El campo password_hash no debe superar los 255 caracteres";
        }

        foreach (['id_docente', 'id_estudiante'] as $campo) {
            if (isset($data[$campo]) && $data[$campo] !== '' && (!is_numeric($data[$campo]) || (int) $data[$campo] < 1)) {
                $errores[] = "El campo $campo debe ser un entero positivo";
            }
        }

        if (isset($data['rol']) && !in_array($data['rol'], ['admin', 'docente', 'estudiante'], true)) {
            $errores[] = "El campo rol debe ser admin, docente o estudiante";
        }
        if ($esNuevo && ($data['rol'] ?? null) === 'estudiante') {
            $errores[] = "Los estudiantes se registran como datos académicos y no como cuentas de acceso";
        }
        if (($data['rol'] ?? null) === 'docente' && empty($data['id_docente'])) {
            $errores[] = "Una cuenta docente debe estar vinculada a un docente";
        }
        if (($data['rol'] ?? null) === 'estudiante' && empty($data['id_estudiante'])) {
            $errores[] = "Una cuenta estudiante debe estar vinculada a un estudiante";
        }

        return $errores;
    }

    private function normalizarRol($data, $esNuevo = false)
    {
        if ($esNuevo && !isset($data['rol'])) {
            $data['rol'] = !empty($data['id_docente'])
                ? 'docente'
                : 'admin';
        }

        return $data;
    }

    private function normalizarContrasena($data)
    {
        if (isset($data['password'])) {
            $password = (string) $data['password'];
            unset($data['password']);
            if ($password !== '') {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
        } elseif (isset($data['password_hash']) && $data['password_hash'] !== '') {
            $password = (string) $data['password_hash'];
            $info = password_get_info($password);
            if (empty($info['algo'])) {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        return $data;
    }
}

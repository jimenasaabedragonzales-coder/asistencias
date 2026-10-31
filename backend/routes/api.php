<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && preg_match('/^https?:\/\/(?:localhost|127\.0\.0\.1|asistencias-e9b3\.onrender\.com|.*\.onrender\.com|.*\.vercel\.app)(?::\d+)?$/i', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$resources = [
    'asistencias' => ['AsistenciasController', 'AsistenciasController.php'],
    'asignaciones' => ['AsignacionesController', 'AsignacionesController.php'],
    'cursos' => ['CursosController', 'CursosController.php'],
    'docentes' => ['DocentesController', 'DocentesController.php'],
    'estudiantes' => ['EstudianteController', 'EstudianteController.php'],
    'inscripciones' => ['InscripcionesController', 'InscripcionesController.php'],
    'materias' => ['MateriasController', 'MateriasController.php'],
    'users' => ['UserController', 'UserController.php'],
    'usuarios' => ['UserController', 'UserController.php'],
];

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $path = trim($path, '/');

    $scriptDirectory = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($scriptDirectory !== '' && ($path === $scriptDirectory || strpos($path, $scriptDirectory . '/') === 0)) {
        $path = ltrim(substr($path, strlen($scriptDirectory)), '/');
    }

    foreach (['routes/api.php/', 'api.php/', 'index.php/', 'backend/', 'api/'] as $prefix) {
        if (strpos($path, $prefix) === 0) {
            $path = substr($path, strlen($prefix));
            break;
        }
    }

    if ($method === 'POST' && $path === 'auth/login') {
        require_once __DIR__ . '/../Controllers/UserController.php';
        (new UserController())->login();
        exit;
    }
    if ($method === 'POST' && $path === 'auth/logout') {
        require_once __DIR__ . '/../Controllers/UserController.php';
        (new UserController())->logout();
        exit;
    }

    if (!preg_match('#^([a-zA-Z_-]+)(?:/([1-9][0-9]*))?$#', $path, $matches)) {
        http_response_code(404);
        echo json_encode(['estado' => false, 'message' => 'Ruta no encontrada']);
        exit;
    }

    $resource = strtolower($matches[1]);
    $id = isset($matches[2]) ? (int) $matches[2] : null;
    if (!isset($resources[$resource])) {
        http_response_code(404);
        echo json_encode(['estado' => false, 'message' => 'Recurso no encontrado']);
        exit;
    }

  $user = $_SESSION['user'] ?? null;
$isPublicLogin = in_array($resource, ['users', 'usuarios'], true) && $method === 'POST';

if (!$isPublicLogin) {
    if (!is_array($user) || !in_array($user['rol'] ?? null, ['admin', 'docente'], true)) {
        http_response_code(401);
        echo json_encode(['estado' => false, 'message' => 'Inicia sesión para continuar']);
        exit;
    }
}

    if (($user['rol'] ?? null) === 'docente') {
        $allowedTeacherMethods = [
            'GET' => ['asignaciones', 'asistencias', 'cursos', 'estudiantes', 'inscripciones', 'materias'],
            'POST' => ['asignaciones', 'asistencias', 'estudiantes', 'inscripciones'],
            'PUT' => ['asistencias'],
        ];
        if (!in_array($resource, $allowedTeacherMethods[$method] ?? [], true)) {
            http_response_code(403);
            echo json_encode(['estado' => false, 'message' => 'No tienes permiso para esta operación']);
            exit;
        }
        if (empty($user['id_docente'])) {
            http_response_code(403);
            echo json_encode(['estado' => false, 'message' => 'La cuenta docente no está vinculada a un docente']);
            exit;
        }
    }

    if ($method === 'GET') {
        $action = $id === null ? 'getAll' : 'getById';
    } elseif ($method === 'POST' && $id === null) {
        $action = 'add';
    } elseif ($method === 'PUT' && $id !== null) {
        $action = 'update';
    } elseif ($method === 'DELETE' && $id !== null) {
        $action = 'delete';
    } else {
        header('Allow: GET, POST, PUT, DELETE, OPTIONS');
        http_response_code(405);
        echo json_encode(['estado' => false, 'message' => 'Método no permitido para esta ruta']);
        exit;
    }

    [$controllerClass, $controllerFile] = $resources[$resource];
    require_once __DIR__ . '/../Controllers/' . $controllerFile;
    $controller = new $controllerClass();

    if ($id === null) {
        $controller->$action();
    } else {
        $controller->$action($id);
    }
} catch (Throwable $error) {
    error_log((string) $error);
    if ($error instanceof PDOException && str_starts_with((string) $error->getCode(), '23')) {
        http_response_code(409);
        $mysqlCode = $error->errorInfo[1] ?? null;
        if ($mysqlCode === 1062) {
            $message = 'Ya existe un registro con esos datos. Revisa el nombre y las características.';
        } elseif ($mysqlCode === 1451) {
            $message = 'No se puede eliminar porque este registro está relacionado con otros datos. Revisa las asignaciones, inscripciones o asistencias.';
        } elseif ($mysqlCode === 1452) {
            $message = 'No se puede guardar porque una de las opciones seleccionadas ya no existe. Actualiza el formulario y vuelve a intentarlo.';
        } else {
            $message = 'La operación entra en conflicto con datos existentes o relacionados.';
        }
        echo json_encode([
            'estado' => false,
            'message' => $message,
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'estado' => false,
        'message' => 'Error interno del servidor',
    ]);
}

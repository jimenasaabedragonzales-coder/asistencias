CREATE DATABASE IF NOT EXISTS dbasistencia;
USE dbasistencia;

-- 1. TABLA DE DOCENTES
CREATE TABLE IF NOT EXISTS DOCENTES (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    telefono VARCHAR(15) NOT NULL DEFAULT '',
    UNIQUE KEY uq_docentes_identidad (nombre, apellido, telefono)
) ENGINE=InnoDB;

-- 2. TABLA DE ESTUDIANTES
CREATE TABLE IF NOT EXISTS ESTUDIANTES (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    direccion VARCHAR(250),
    telefono VARCHAR(15)
) ENGINE=InnoDB;

-- 3. TABLA DE USUARIOS
CREATE TABLE IF NOT EXISTS USUARIOS (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'docente', 'estudiante')
        NOT NULL DEFAULT 'admin',
    id_docente INT NULL,
    id_estudiante INT NULL,
    FOREIGN KEY (id_docente)
        REFERENCES DOCENTES(id) ON DELETE SET NULL,
    FOREIGN KEY (id_estudiante)
        REFERENCES ESTUDIANTES(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. TABLA DE CURSOS
CREATE TABLE IF NOT EXISTS CURSOS (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nombre_curso VARCHAR(50) NOT NULL,
    nivel VARCHAR(30) NOT NULL,
    paralelo VARCHAR(10) NOT NULL,
    UNIQUE KEY uq_cursos_identidad (nombre_curso, nivel, paralelo)
) ENGINE=InnoDB;

-- 5. TABLA DE MATERIAS
CREATE TABLE IF NOT EXISTS MATERIAS (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nombre_materia VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_materias_nombre (nombre_materia)
) ENGINE=InnoDB;

-- 6. TABLA DE ASIGNACIONES
CREATE TABLE IF NOT EXISTS ASIGNACIONES (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    cod_docente INT NOT NULL,
    cod_materia INT NOT NULL,
    cod_curso INT NOT NULL,
    UNIQUE KEY uq_asignacion_docente_materia_curso (cod_docente, cod_materia, cod_curso),
    FOREIGN KEY (cod_docente) REFERENCES DOCENTES(id),
    FOREIGN KEY (cod_materia) REFERENCES MATERIAS(id),
    FOREIGN KEY (cod_curso) REFERENCES CURSOS(id)
) ENGINE=InnoDB;

-- 7. TABLA DE INSCRIPCIONES
CREATE TABLE IF NOT EXISTS INSCRIPCIONES (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    cod_estudiante INT NOT NULL,
    cod_curso INT NOT NULL,
    gestion YEAR NOT NULL,
    UNIQUE KEY uq_inscripcion (cod_estudiante, gestion),
    FOREIGN KEY (cod_estudiante)
        REFERENCES ESTUDIANTES(id),
    FOREIGN KEY (cod_curso)
        REFERENCES CURSOS(id)
) ENGINE=InnoDB;

-- 8. TABLA DE ASISTENCIAS
CREATE TABLE IF NOT EXISTS ASISTENCIAS (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    cod_estudiante INT NOT NULL,
    cod_asignacion INT NOT NULL,
    cod_usuario_registro INT NULL,
    fecha DATE NOT NULL,
    estado ENUM(
        'Presente',
        'Ausente',
        'Licencia',
        'Retraso'
    ) NOT NULL,
    observacion VARCHAR(250),
    FOREIGN KEY (cod_estudiante)
        REFERENCES ESTUDIANTES(id),
    FOREIGN KEY (cod_asignacion)
        REFERENCES ASIGNACIONES(id),
    FOREIGN KEY (cod_usuario_registro)
        REFERENCES USUARIOS(id)
) ENGINE=InnoDB;

-- =====================================================
-- DATOS DE PRUEBA
-- Las inserciones son idempotentes: se omiten los registros de ejemplo
-- que ya existen. No se eliminan ni modifican datos del usuario.
-- =====================================================

-- DOCENTES
INSERT INTO DOCENTES (nombre, apellido, telefono)
SELECT 'Carlos', 'Mendoza', '71234567'
WHERE NOT EXISTS (SELECT 1 FROM DOCENTES WHERE nombre='Carlos' AND apellido='Mendoza' AND telefono='71234567');
INSERT INTO DOCENTES (nombre, apellido, telefono)
SELECT 'Ana', 'Rodriguez', '72345678'
WHERE NOT EXISTS (SELECT 1 FROM DOCENTES WHERE nombre='Ana' AND apellido='Rodriguez' AND telefono='72345678');
INSERT INTO DOCENTES (nombre, apellido, telefono)
SELECT 'Walter', 'Gomez', '73456789'
WHERE NOT EXISTS (SELECT 1 FROM DOCENTES WHERE nombre='Walter' AND apellido='Gomez' AND telefono='73456789');

-- ESTUDIANTES
INSERT INTO ESTUDIANTES (nombre, apellido, direccion, telefono)
SELECT 'Luis', 'Flores', 'Av. Las Delicias #123', '61234567'
WHERE NOT EXISTS (SELECT 1 FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567');
INSERT INTO ESTUDIANTES (nombre, apellido, direccion, telefono)
SELECT 'Maria', 'Gomez', 'Calle Bolivar #45', '62345678'
WHERE NOT EXISTS (SELECT 1 FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678');
INSERT INTO ESTUDIANTES (nombre, apellido, direccion, telefono)
SELECT 'Pedro', 'Mamani', 'Zona Central Nro 789', '63456789'
WHERE NOT EXISTS (SELECT 1 FROM ESTUDIANTES WHERE nombre='Pedro' AND apellido='Mamani' AND direccion='Zona Central Nro 789' AND telefono='63456789');
INSERT INTO ESTUDIANTES (nombre, apellido, direccion, telefono)
SELECT 'Lucia', 'Torres', 'Barrio Lindo Calle 2', '64567890'
WHERE NOT EXISTS (SELECT 1 FROM ESTUDIANTES WHERE nombre='Lucia' AND apellido='Torres' AND direccion='Barrio Lindo Calle 2' AND telefono='64567890');

-- USUARIOS
-- Cuentas de demostración. Se omiten usernames ya existentes.
INSERT INTO USUARIOS
(username, password_hash, rol, id_docente, id_estudiante)
SELECT 'carlos.m',
       '$2y$12$tyeiTX3ywccpssIRLlvmYeFM3QDW3ACQgrFY0ELgl95IzX6WzxSq.',
       'docente',
       (SELECT id FROM DOCENTES WHERE nombre = 'Carlos' AND apellido = 'Mendoza' ORDER BY id LIMIT 1),
       NULL
WHERE NOT EXISTS (SELECT 1 FROM USUARIOS WHERE username = 'carlos.m')
UNION ALL
SELECT 'ana.r',
       '$2y$12$D7TevHBmd6P8xmK9vbMyxevhttVDoCwbdLT3v2OBV1j3jf6O1LOEW',
       'docente',
       (SELECT id FROM DOCENTES WHERE nombre = 'Ana' AND apellido = 'Rodriguez' ORDER BY id LIMIT 1),
       NULL
WHERE NOT EXISTS (SELECT 1 FROM USUARIOS WHERE username = 'ana.r')
UNION ALL
SELECT 'admin.sistema',
       '$2y$12$K/q/vb5bX7DLcoq6dphnP.l8TopD.xrvtcIGFxSh1cxg6.6F/01Be',
       'admin',
       NULL,
       NULL
WHERE NOT EXISTS (SELECT 1 FROM USUARIOS WHERE username = 'admin.sistema');

-- CURSOS
INSERT INTO CURSOS (nombre_curso, nivel, paralelo)
SELECT 'Primero de Secundaria', 'Secundaria', 'A'
WHERE NOT EXISTS (SELECT 1 FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='A');
INSERT INTO CURSOS (nombre_curso, nivel, paralelo)
SELECT 'Primero de Secundaria', 'Secundaria', 'B'
WHERE NOT EXISTS (SELECT 1 FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='B');
INSERT INTO CURSOS (nombre_curso, nivel, paralelo)
SELECT 'Segundo de Secundaria', 'Secundaria', 'A'
WHERE NOT EXISTS (SELECT 1 FROM CURSOS WHERE nombre_curso='Segundo de Secundaria' AND nivel='Secundaria' AND paralelo='A');

-- MATERIAS
INSERT INTO MATERIAS (nombre_materia)
SELECT 'Matemáticas'
WHERE NOT EXISTS (SELECT 1 FROM MATERIAS WHERE nombre_materia='Matemáticas');
INSERT INTO MATERIAS (nombre_materia)
SELECT 'Lenguaje y Literatura'
WHERE NOT EXISTS (SELECT 1 FROM MATERIAS WHERE nombre_materia='Lenguaje y Literatura');
INSERT INTO MATERIAS (nombre_materia)
SELECT 'Física'
WHERE NOT EXISTS (SELECT 1 FROM MATERIAS WHERE nombre_materia='Física');

-- ASIGNACIONES
INSERT INTO ASIGNACIONES (cod_docente, cod_materia, cod_curso)
SELECT
    (SELECT id FROM DOCENTES WHERE nombre='Carlos' AND apellido='Mendoza' AND telefono='71234567' ORDER BY id LIMIT 1),
    (SELECT id FROM MATERIAS WHERE nombre_materia='Matemáticas' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM ASIGNACIONES a
    JOIN DOCENTES d ON d.id=a.cod_docente
    JOIN MATERIAS m ON m.id=a.cod_materia
    JOIN CURSOS c ON c.id=a.cod_curso
    WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND d.telefono='71234567'
      AND m.nombre_materia='Matemáticas' AND c.nombre_curso='Primero de Secundaria'
      AND c.nivel='Secundaria' AND c.paralelo='A'
);
INSERT INTO ASIGNACIONES (cod_docente, cod_materia, cod_curso)
SELECT
    (SELECT id FROM DOCENTES WHERE nombre='Carlos' AND apellido='Mendoza' AND telefono='71234567' ORDER BY id LIMIT 1),
    (SELECT id FROM MATERIAS WHERE nombre_materia='Física' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Segundo de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM ASIGNACIONES a
    JOIN DOCENTES d ON d.id=a.cod_docente
    JOIN MATERIAS m ON m.id=a.cod_materia
    JOIN CURSOS c ON c.id=a.cod_curso
    WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND d.telefono='71234567'
      AND m.nombre_materia='Física' AND c.nombre_curso='Segundo de Secundaria'
      AND c.nivel='Secundaria' AND c.paralelo='A'
);
INSERT INTO ASIGNACIONES (cod_docente, cod_materia, cod_curso)
SELECT
    (SELECT id FROM DOCENTES WHERE nombre='Ana' AND apellido='Rodriguez' AND telefono='72345678' ORDER BY id LIMIT 1),
    (SELECT id FROM MATERIAS WHERE nombre_materia='Lenguaje y Literatura' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM ASIGNACIONES a
    JOIN DOCENTES d ON d.id=a.cod_docente
    JOIN MATERIAS m ON m.id=a.cod_materia
    JOIN CURSOS c ON c.id=a.cod_curso
    WHERE d.nombre='Ana' AND d.apellido='Rodriguez' AND d.telefono='72345678'
      AND m.nombre_materia='Lenguaje y Literatura' AND c.nombre_curso='Primero de Secundaria'
      AND c.nivel='Secundaria' AND c.paralelo='A'
);

-- INSCRIPCIONES
INSERT INTO INSCRIPCIONES (cod_estudiante, cod_curso, gestion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1),
    2026
WHERE NOT EXISTS (SELECT 1 FROM INSCRIPCIONES i JOIN ESTUDIANTES e ON e.id=i.cod_estudiante WHERE e.nombre='Luis' AND e.apellido='Flores' AND e.direccion='Av. Las Delicias #123' AND e.telefono='61234567' AND i.gestion=2026);
INSERT INTO INSCRIPCIONES (cod_estudiante, cod_curso, gestion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1),
    2026
WHERE NOT EXISTS (SELECT 1 FROM INSCRIPCIONES i JOIN ESTUDIANTES e ON e.id=i.cod_estudiante WHERE e.nombre='Maria' AND e.apellido='Gomez' AND e.direccion='Calle Bolivar #45' AND e.telefono='62345678' AND i.gestion=2026);
INSERT INTO INSCRIPCIONES (cod_estudiante, cod_curso, gestion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Pedro' AND apellido='Mamani' AND direccion='Zona Central Nro 789' AND telefono='63456789' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Primero de Secundaria' AND nivel='Secundaria' AND paralelo='B' ORDER BY id LIMIT 1),
    2026
WHERE NOT EXISTS (SELECT 1 FROM INSCRIPCIONES i JOIN ESTUDIANTES e ON e.id=i.cod_estudiante WHERE e.nombre='Pedro' AND e.apellido='Mamani' AND e.direccion='Zona Central Nro 789' AND e.telefono='63456789' AND i.gestion=2026);
INSERT INTO INSCRIPCIONES (cod_estudiante, cod_curso, gestion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Lucia' AND apellido='Torres' AND direccion='Barrio Lindo Calle 2' AND telefono='64567890' ORDER BY id LIMIT 1),
    (SELECT id FROM CURSOS WHERE nombre_curso='Segundo de Secundaria' AND nivel='Secundaria' AND paralelo='A' ORDER BY id LIMIT 1),
    2026
WHERE NOT EXISTS (SELECT 1 FROM INSCRIPCIONES i JOIN ESTUDIANTES e ON e.id=i.cod_estudiante WHERE e.nombre='Lucia' AND e.apellido='Torres' AND e.direccion='Barrio Lindo Calle 2' AND e.telefono='64567890' AND i.gestion=2026);

-- ASISTENCIAS
INSERT INTO ASISTENCIAS (cod_estudiante, cod_asignacion, cod_usuario_registro, fecha, estado, observacion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567' ORDER BY id LIMIT 1),
    (SELECT a.id FROM ASIGNACIONES a JOIN DOCENTES d ON d.id=a.cod_docente JOIN MATERIAS m ON m.id=a.cod_materia JOIN CURSOS c ON c.id=a.cod_curso WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND m.nombre_materia='Matemáticas' AND c.nombre_curso='Primero de Secundaria' AND c.nivel='Secundaria' AND c.paralelo='A' ORDER BY a.id LIMIT 1),
    (SELECT id FROM USUARIOS WHERE username='carlos.m' LIMIT 1),
    '2026-03-02', 'Presente', 'Llegó puntual a matemáticas'
WHERE NOT EXISTS (SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=(SELECT id FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567' ORDER BY id LIMIT 1) AND fecha='2026-03-02');
INSERT INTO ASISTENCIAS (cod_estudiante, cod_asignacion, cod_usuario_registro, fecha, estado, observacion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678' ORDER BY id LIMIT 1),
    (SELECT a.id FROM ASIGNACIONES a JOIN DOCENTES d ON d.id=a.cod_docente JOIN MATERIAS m ON m.id=a.cod_materia JOIN CURSOS c ON c.id=a.cod_curso WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND m.nombre_materia='Matemáticas' AND c.nombre_curso='Primero de Secundaria' AND c.nivel='Secundaria' AND c.paralelo='A' ORDER BY a.id LIMIT 1),
    (SELECT id FROM USUARIOS WHERE username='carlos.m' LIMIT 1),
    '2026-03-02', 'Retraso', 'Demora por transporte público'
WHERE NOT EXISTS (SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=(SELECT id FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678' ORDER BY id LIMIT 1) AND fecha='2026-03-02');
INSERT INTO ASISTENCIAS (cod_estudiante, cod_asignacion, cod_usuario_registro, fecha, estado, observacion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567' ORDER BY id LIMIT 1),
    (SELECT a.id FROM ASIGNACIONES a JOIN DOCENTES d ON d.id=a.cod_docente JOIN MATERIAS m ON m.id=a.cod_materia JOIN CURSOS c ON c.id=a.cod_curso WHERE d.nombre='Ana' AND d.apellido='Rodriguez' AND m.nombre_materia='Lenguaje y Literatura' AND c.nombre_curso='Primero de Secundaria' AND c.nivel='Secundaria' AND c.paralelo='A' ORDER BY a.id LIMIT 1),
    (SELECT id FROM USUARIOS WHERE username='ana.r' LIMIT 1),
    '2026-03-03', 'Presente', NULL
WHERE NOT EXISTS (SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=(SELECT id FROM ESTUDIANTES WHERE nombre='Luis' AND apellido='Flores' AND direccion='Av. Las Delicias #123' AND telefono='61234567' ORDER BY id LIMIT 1) AND fecha='2026-03-03');
INSERT INTO ASISTENCIAS (cod_estudiante, cod_asignacion, cod_usuario_registro, fecha, estado, observacion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Lucia' AND apellido='Torres' AND direccion='Barrio Lindo Calle 2' AND telefono='64567890' ORDER BY id LIMIT 1),
    (SELECT a.id FROM ASIGNACIONES a JOIN DOCENTES d ON d.id=a.cod_docente JOIN MATERIAS m ON m.id=a.cod_materia JOIN CURSOS c ON c.id=a.cod_curso WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND m.nombre_materia='Física' AND c.nombre_curso='Segundo de Secundaria' AND c.nivel='Secundaria' AND c.paralelo='A' ORDER BY a.id LIMIT 1),
    (SELECT id FROM USUARIOS WHERE username='admin.sistema' LIMIT 1),
    '2026-03-03', 'Ausente', 'Falta sin justificar registrada por admin'
WHERE NOT EXISTS (SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=(SELECT id FROM ESTUDIANTES WHERE nombre='Lucia' AND apellido='Torres' AND direccion='Barrio Lindo Calle 2' AND telefono='64567890' ORDER BY id LIMIT 1) AND fecha='2026-03-03');
INSERT INTO ASISTENCIAS (cod_estudiante, cod_asignacion, cod_usuario_registro, fecha, estado, observacion)
SELECT
    (SELECT id FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678' ORDER BY id LIMIT 1),
    (SELECT a.id FROM ASIGNACIONES a JOIN DOCENTES d ON d.id=a.cod_docente JOIN MATERIAS m ON m.id=a.cod_materia JOIN CURSOS c ON c.id=a.cod_curso WHERE d.nombre='Carlos' AND d.apellido='Mendoza' AND m.nombre_materia='Matemáticas' AND c.nombre_curso='Primero de Secundaria' AND c.nivel='Secundaria' AND c.paralelo='A' ORDER BY a.id LIMIT 1),
    (SELECT id FROM USUARIOS WHERE username='carlos.m' LIMIT 1),
    '2026-03-04', 'Licencia', 'Presentó justificativo médico'
WHERE NOT EXISTS (SELECT 1 FROM ASISTENCIAS WHERE cod_estudiante=(SELECT id FROM ESTUDIANTES WHERE nombre='Maria' AND apellido='Gomez' AND direccion='Calle Bolivar #45' AND telefono='62345678' ORDER BY id LIMIT 1) AND fecha='2026-03-04');

-- =====================================================
-- CONSULTAS PARA VERIFICAR LOS DATOS
-- =====================================================

SELECT * FROM DOCENTES;
SELECT * FROM ESTUDIANTES;
SELECT * FROM USUARIOS;
SELECT * FROM CURSOS;
SELECT * FROM MATERIAS;
SELECT * FROM ASIGNACIONES;
SELECT * FROM INSCRIPCIONES;
SELECT * FROM ASISTENCIAS;

-- Identifica docentes duplicados sin modificar ni eliminar registros.
SELECT
    LOWER(TRIM(nombre)) AS nombre,
    LOWER(TRIM(apellido)) AS apellido,
    COALESCE(NULLIF(TRIM(telefono), ''), '') AS telefono,
    COUNT(*) AS cantidad,
    GROUP_CONCAT(id ORDER BY id SEPARATOR ', ') AS ids
FROM DOCENTES
GROUP BY
    LOWER(TRIM(nombre)),
    LOWER(TRIM(apellido)),
    COALESCE(NULLIF(TRIM(telefono), ''), '')
HAVING COUNT(*) > 1;

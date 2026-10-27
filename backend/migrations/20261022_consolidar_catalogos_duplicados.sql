-- Ejecutar una sola vez en la base dbasistencia, después de respaldarla.
-- Consolida docentes, cursos y materias equivalentes y redirige relaciones
-- antes de eliminar las filas repetidas. Las asistencias se conservan.
START TRANSACTION;

CREATE TEMPORARY TABLE tmp_docentes_merge ENGINE=InnoDB AS
SELECT d.id AS duplicate_id, MIN(c.id) AS keep_id
FROM DOCENTES d
JOIN DOCENTES c ON c.id < d.id
    AND LOWER(TRIM(c.nombre)) = LOWER(TRIM(d.nombre))
    AND LOWER(TRIM(c.apellido)) = LOWER(TRIM(d.apellido))
    AND COALESCE(NULLIF(TRIM(c.telefono), ''), '') = COALESCE(NULLIF(TRIM(d.telefono), ''), '')
GROUP BY d.id;

CREATE TEMPORARY TABLE tmp_cursos_merge ENGINE=InnoDB AS
SELECT d.id AS duplicate_id, MIN(c.id) AS keep_id
FROM CURSOS d
JOIN CURSOS c ON c.id < d.id
    AND LOWER(TRIM(c.nombre_curso)) = LOWER(TRIM(d.nombre_curso))
    AND LOWER(TRIM(c.nivel)) = LOWER(TRIM(d.nivel))
    AND LOWER(TRIM(c.paralelo)) = LOWER(TRIM(d.paralelo))
GROUP BY d.id;

CREATE TEMPORARY TABLE tmp_materias_merge ENGINE=InnoDB AS
SELECT d.id AS duplicate_id, MIN(c.id) AS keep_id
FROM MATERIAS d
JOIN MATERIAS c ON c.id < d.id
    AND LOWER(TRIM(c.nombre_materia)) = LOWER(TRIM(d.nombre_materia))
GROUP BY d.id;

-- Mantener cuentas y asignaciones en el docente que se conserva.
UPDATE USUARIOS u JOIN tmp_docentes_merge m ON u.id_docente=m.duplicate_id SET u.id_docente=m.keep_id;
UPDATE ASIGNACIONES a JOIN tmp_docentes_merge m ON a.cod_docente=m.duplicate_id SET a.cod_docente=m.keep_id;

-- Mantener asignaciones e inscripciones al consolidar cursos y materias.
UPDATE ASIGNACIONES a JOIN tmp_cursos_merge m ON a.cod_curso=m.duplicate_id SET a.cod_curso=m.keep_id;
UPDATE INSCRIPCIONES i JOIN tmp_cursos_merge m ON i.cod_curso=m.duplicate_id SET i.cod_curso=m.keep_id;
UPDATE ASIGNACIONES a JOIN tmp_materias_merge m ON a.cod_materia=m.duplicate_id SET a.cod_materia=m.keep_id;

-- Redirigir asistencias antes de retirar asignaciones equivalentes creadas
-- cuando se fusionan docentes, cursos o materias.
CREATE TEMPORARY TABLE tmp_asignaciones_merge ENGINE=InnoDB AS
SELECT a.id AS duplicate_id, MIN(c.id) AS keep_id
FROM ASIGNACIONES a
JOIN ASIGNACIONES c ON c.id < a.id
    AND c.cod_docente=a.cod_docente
    AND c.cod_materia=a.cod_materia
    AND c.cod_curso=a.cod_curso
GROUP BY a.id;
UPDATE ASISTENCIAS x JOIN tmp_asignaciones_merge m ON x.cod_asignacion=m.duplicate_id SET x.cod_asignacion=m.keep_id;
DELETE a FROM ASIGNACIONES a JOIN tmp_asignaciones_merge m ON a.id=m.duplicate_id;

DELETE d FROM DOCENTES d JOIN tmp_docentes_merge m ON d.id=m.duplicate_id;
DELETE c FROM CURSOS c JOIN tmp_cursos_merge m ON c.id=m.duplicate_id;
DELETE s FROM MATERIAS s JOIN tmp_materias_merge m ON s.id=m.duplicate_id;
COMMIT;

-- Impiden que vuelvan a crearse duplicados exactos. La API compara además
-- ignorando mayúsculas y espacios al principio/final.
UPDATE DOCENTES SET telefono='' WHERE telefono IS NULL OR TRIM(telefono)='';
ALTER TABLE DOCENTES ADD UNIQUE KEY uq_docentes_identidad (nombre, apellido, telefono);
ALTER TABLE CURSOS ADD UNIQUE KEY uq_cursos_identidad (nombre_curso, nivel, paralelo);
ALTER TABLE MATERIAS ADD UNIQUE KEY uq_materias_nombre (nombre_materia);
ALTER TABLE ASIGNACIONES ADD UNIQUE KEY uq_asignacion_docente_materia_curso (cod_docente, cod_materia, cod_curso);

DROP TEMPORARY TABLE tmp_docentes_merge;
DROP TEMPORARY TABLE tmp_cursos_merge;
DROP TEMPORARY TABLE tmp_materias_merge;
DROP TEMPORARY TABLE tmp_asignaciones_merge;

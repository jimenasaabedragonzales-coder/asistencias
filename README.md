# Control de Asistencias

Aplicación local para administrar estudiantes, docentes, cursos, materias, asignaciones, inscripciones y asistencias. Conserva la estructura de pantallas actual y usa PHP, MySQL/MariaDB de Wamp y páginas estáticas.

## Instalación en Wamp

1. Copia la carpeta `Asistencia` dentro de `C:\wamp64\www` y comprueba que Wamp muestre sus servicios activos.
2. En Navicat, ejecuta `sistema_asostencia.sql`. El archivo crea `dbasistencia_nueva` y sus ocho tablas vacías. No inserta usuarios, ejemplos ni asistencias.
3. Abre `http://localhost/Asistencia/frontend/`.
4. En el primer acceso aparece **Crear administrador**. Escribe el usuario y la contraseña que quieras usar. Esa acción crea únicamente esa cuenta y abre la sesión; no hay credenciales predeterminadas.
5. Desde el panel registra los docentes, materias, cursos, estudiantes, asignaciones e inscripciones que correspondan.

El backend usa por defecto `localhost:3306`, la base `dbasistencia_nueva` y el usuario `root` sin contraseña, que son los valores habituales de Wamp local. Si tu instalación usa otros datos, copia `backend/.env.example` a `backend/.env` y ajusta allí la conexión. `backend/.env` es local, está excluido de Git y no debe usarse como respaldo para trasladar el sistema.

## Trasladar el sistema

- Para instalarlo vacío en otra laptop, copia la carpeta, ejecuta allí `sistema_asostencia.sql` y crea la primera cuenta desde la página.
- Para trasladar registros que ingreses luego, exporta `dbasistencia_nueva` desde Navicat incluyendo estructura y datos, e impórtala en la otra instalación. El respaldo de `dbasistencia` corresponde a la base anterior y no forma parte de la instalación limpia.
- Mantén el mismo nombre de base (`dbasistencia_nueva`) o cambia `DB_NAME` en `backend/.env`.

## Comprobación de la instalación

En Navicat, `dbasistencia_nueva` debe contener las tablas `DOCENTES`, `ESTUDIANTES`, `USUARIOS`, `CURSOS`, `MATERIAS`, `ASIGNACIONES`, `INSCRIPCIONES` y `ASISTENCIAS`. Antes de crear la primera cuenta, todas estarán vacías. La aplicación valida las relaciones y evita registrar dos veces la asistencia del mismo estudiante, asignación y fecha.

El CSS ya compilado está incluido. Para usar el sistema no hace falta instalar Node.js ni ejecutar `npm install`.

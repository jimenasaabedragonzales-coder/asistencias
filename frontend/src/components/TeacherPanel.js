import { api } from '../utils/api.js?v=20261022-catalog-cleanup-v1';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const showMessage = (elementId, message, isError = false) => {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.className = `mt-3 rounded-md px-4 py-3 text-sm font-semibold ${isError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`;
};

const optionList = (items, getLabel) => `<option value="">Selecciona una opción</option>${items.map((item) => `
    <option value="${escapeHtml(item.id)}">${escapeHtml(getLabel(item))}</option>
`).join('')}`;

const localDate = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
};

export const initTeacherPanel = async (session) => {
    if (!session.id_docente) throw new Error('La cuenta docente no está vinculada a un docente.');

    const [assignments, subjects, courses, students, enrollments, attendanceRecords] = await Promise.all([
        api.get('asignaciones'),
        api.get('materias'),
        api.get('cursos'),
        api.get('estudiantes'),
        api.get('inscripciones'),
        api.get('asistencias'),
    ]);
    for (const [name, value] of Object.entries({ assignments, subjects, courses, students, enrollments, attendanceRecords })) {
        if (!Array.isArray(value)) throw new Error(`El API devolvió datos inválidos para ${name}.`);
    }

    const teacherAssignments = assignments;
    const assignedCourseIds = [...new Set(teacherAssignments.map((item) => Number(item.cod_curso)))];
    const teacherCourses = courses.filter((course) => assignedCourseIds.includes(Number(course.id)));
    const subjectSelect = document.getElementById('TeacherSubject');
    const courseSelect = document.getElementById('TeacherCourse');
    const assignmentForm = document.getElementById('TeacherAssignmentForm');
    const assignmentList = document.getElementById('TeacherAssignments');
    const enrollmentCourse = document.getElementById('EnrollmentCourse');
    const enrollmentStudent = document.getElementById('EnrollmentStudent');
    const enrollmentYear = document.getElementById('EnrollmentYear');
    const roster = document.getElementById('TeacherRoster');
    const attendanceAssignment = document.getElementById('AttendanceAssignment');
    const attendanceStudent = document.getElementById('AttendanceStudent');
    const attendanceDate = document.getElementById('AttendanceDate');
    const historyAssignment = document.getElementById('TeacherHistoryAssignment');
    const historyRows = document.getElementById('TeacherAttendanceHistoryRows');
    const editAttendanceForm = document.getElementById('TeacherAttendanceEditForm');
    const year = new Date().getFullYear();

    subjectSelect.innerHTML = optionList(subjects, (subject) => subject.nombre_materia);
    courseSelect.innerHTML = optionList(courses, (course) => `${course.nombre_curso} · ${course.nivel} ${course.paralelo}`);

    const refreshAssignmentOptions = () => {
        enrollmentCourse.innerHTML = optionList(teacherCourses, (course) => `${course.nombre_curso} · ${course.nivel} ${course.paralelo}`);
        const assignmentLabel = (assignment) => {
            const subject = subjects.find((item) => Number(item.id) === Number(assignment.cod_materia));
            const course = courses.find((item) => Number(item.id) === Number(assignment.cod_curso));
            return `${subject?.nombre_materia || 'Materia'} · ${course?.nombre_curso || 'Curso'} ${course?.paralelo || ''}`;
        };
        attendanceAssignment.innerHTML = optionList(teacherAssignments, assignmentLabel);
        historyAssignment.innerHTML = optionList(teacherAssignments, (assignment) => {
            const course = courses.find((item) => Number(item.id) === Number(assignment.cod_curso));
            return `${assignmentLabel(assignment)} · ${course?.nivel || ''}`;
        });
    };

    refreshAssignmentOptions();
    enrollmentYear.value = year;
    attendanceDate.value = localDate();

    const renderAssignments = () => {
        assignmentList.innerHTML = teacherAssignments.length ? teacherAssignments.map((assignment) => {
            const subject = subjects.find((item) => Number(item.id) === Number(assignment.cod_materia));
            const course = courses.find((item) => Number(item.id) === Number(assignment.cod_curso));
            return `<article class="rounded-lg border border-cyan-100 bg-cyan-50 p-4">
                <p class="font-bold text-slate-900">${escapeHtml(subject?.nombre_materia || `Materia ${assignment.cod_materia}`)}</p>
                <p class="mt-1 text-sm text-slate-600">${escapeHtml(course ? `${course.nombre_curso} · ${course.nivel} ${course.paralelo}` : `Curso ${assignment.cod_curso}`)}</p>
            </article>`;
        }).join('') : '<p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600 sm:col-span-2 xl:col-span-3">Todavía no tienes materias o cursos asignados.</p>';
    };

    assignmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = {
            cod_docente: Number(session.id_docente),
            cod_materia: Number(subjectSelect.value),
            cod_curso: Number(courseSelect.value),
        };
        if (teacherAssignments.some((item) => Number(item.cod_materia) === payload.cod_materia
            && Number(item.cod_curso) === payload.cod_curso)) {
            showMessage('TeacherAssignmentMessage', 'Esa materia ya está asignada a ese curso.', true);
            return;
        }

        const submit = assignmentForm.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const response = await api.post('asignaciones', payload);
            if (response?.estado !== true) throw new Error(response?.message || 'No se pudo agregar la asignación.');
            const refreshed = await api.get('asignaciones');
            if (!Array.isArray(refreshed)) throw new Error('La asignación se guardó, pero no se pudo actualizar la lista.');
            teacherAssignments.splice(0, teacherAssignments.length, ...refreshed);
            const courseId = payload.cod_curso;
            if (!assignedCourseIds.includes(courseId)) {
                assignedCourseIds.push(courseId);
                const course = courses.find((item) => Number(item.id) === courseId);
                if (course) teacherCourses.push(course);
            }
            assignmentForm.reset();
            renderAssignments();
            refreshAssignmentOptions();
            refreshRoster();
            refreshAttendanceStudents();
            renderAttendanceHistory();
            showMessage('TeacherAssignmentMessage', response.message);
        } catch (error) {
            showMessage('TeacherAssignmentMessage', error.message, true);
        } finally {
            submit.disabled = false;
        }
    });

    const refreshRoster = () => {
        const courseId = Number(enrollmentCourse.value);
        const activeYear = Number(enrollmentYear.value) || year;
        const enrolledIds = new Set(enrollments
            .filter((item) => Number(item.cod_curso) === courseId && Number(item.gestion) === activeYear)
            .map((item) => Number(item.cod_estudiante)));
        const enrolledStudents = students.filter((student) => enrolledIds.has(Number(student.id)));
        roster.innerHTML = enrolledStudents.length ? `<table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500"><tr><th class="px-3 py-2">Estudiante</th><th class="px-3 py-2">Teléfono</th></tr></thead>
            <tbody class="divide-y divide-slate-100">${enrolledStudents.map((student) => `<tr><td class="px-3 py-2">${escapeHtml(`${student.nombre} ${student.apellido}`)}</td><td class="px-3 py-2">${escapeHtml(student.telefono)}</td></tr>`).join('')}</tbody>
        </table>` : '<p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No hay estudiantes inscritos en este curso para la gestión seleccionada.</p>';

        const available = students.filter((student) => !enrolledIds.has(Number(student.id)));
        enrollmentStudent.innerHTML = optionList(available, (student) => `${student.nombre} ${student.apellido}`);
        return enrolledIds;
    };

    const refreshAttendanceStudents = () => {
        const assignment = teacherAssignments.find((item) => Number(item.id) === Number(attendanceAssignment.value));
        const courseId = Number(assignment?.cod_curso);
        const enrolledIds = new Set(enrollments
            .filter((item) => Number(item.cod_curso) === courseId && Number(item.gestion) === year)
            .map((item) => Number(item.cod_estudiante)));
        const enrolledStudents = students.filter((student) => enrolledIds.has(Number(student.id)));
        attendanceStudent.innerHTML = optionList(enrolledStudents, (student) => `${student.nombre} ${student.apellido}`);
    };

    const renderAttendanceHistory = () => {
        const assignmentId = Number(historyAssignment.value);
        if (!assignmentId) {
            historyRows.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Selecciona una materia y curso.</td></tr>';
            return;
        }

        const records = attendanceRecords
            .filter((record) => Number(record.cod_asignacion) === assignmentId)
            .sort((left, right) => right.fecha.localeCompare(left.fecha) || Number(right.id) - Number(left.id));
        historyRows.innerHTML = records.length ? records.map((record) => {
            const student = students.find((item) => Number(item.id) === Number(record.cod_estudiante));
            return `<tr>
                <td class="whitespace-nowrap px-4 py-3">${escapeHtml(record.fecha)}</td>
                <td class="px-4 py-3">${escapeHtml(student ? `${student.nombre} ${student.apellido}` : `Estudiante ${record.cod_estudiante}`)}</td>
                <td class="px-4 py-3">${escapeHtml(record.estado)}</td>
                <td class="px-4 py-3">${escapeHtml(record.observacion || '')}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <button type="button" data-edit-attendance="${escapeHtml(record.id)}" class="mr-2 rounded px-2 py-1 font-semibold text-cyan-700 hover:bg-cyan-50">Corregir</button>
                </td>
            </tr>`;
        }).join('') : '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay asistencias registradas para esta materia y curso.</td></tr>';
    };

    const clearAttendanceEdit = () => {
        editAttendanceForm.reset();
        document.getElementById('TeacherEditAttendanceId').value = '';
        editAttendanceForm.hidden = true;
    };

    const saveEnrollment = async (event) => {
        event.preventDefault();
        const payload = {
            cod_estudiante: Number(enrollmentStudent.value),
            cod_curso: Number(enrollmentCourse.value),
            gestion: Number(enrollmentYear.value),
        };
        try {
            const response = await api.post('inscripciones', payload);
            if (response?.estado !== true) throw new Error(response?.message || 'No se pudo inscribir al estudiante.');
            enrollments.push({ id: Date.now(), ...payload });
            refreshRoster();
            showMessage('TeacherEnrollmentMessage', response.message);
        } catch (error) {
            showMessage('TeacherEnrollmentMessage', error.message, true);
        }
    };

    const enrollmentForm = document.getElementById('TeacherEnrollmentForm');
    enrollmentForm.addEventListener('submit', async (event) => {
        const submit = enrollmentForm.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            await saveEnrollment(event);
        } finally {
            submit.disabled = false;
        }
    });
    enrollmentCourse.addEventListener('change', refreshRoster);
    enrollmentYear.addEventListener('change', refreshRoster);
    attendanceAssignment.addEventListener('change', refreshAttendanceStudents);

    const attendanceForm = document.getElementById('TeacherAttendanceForm');
    attendanceForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = attendanceForm.querySelector('[type="submit"]');
        submit.disabled = true;
        const payload = {
            cod_estudiante: Number(attendanceStudent.value),
            cod_asignacion: Number(attendanceAssignment.value),
            cod_usuario_registro: Number(session.id),
            fecha: attendanceDate.value,
            estado: document.getElementById('AttendanceState').value,
            observacion: document.getElementById('AttendanceNote').value.trim(),
        };
        try {
            const selectedAssignment = attendanceAssignment.value;
            const response = await api.post('asistencias', payload);
            if (response?.estado !== true) throw new Error(response?.message || 'No se pudo guardar la asistencia.');
            const refreshed = await api.get('asistencias');
            if (!Array.isArray(refreshed)) throw new Error('La asistencia se guardó, pero no se pudo actualizar el historial.');
            attendanceRecords.splice(0, attendanceRecords.length, ...refreshed);
            document.getElementById('TeacherAttendanceForm').reset();
            attendanceAssignment.value = selectedAssignment;
            attendanceDate.value = localDate();
            refreshAttendanceStudents();
            renderAttendanceHistory();
            showMessage('TeacherAttendanceMessage', response.message);
        } catch (error) {
            showMessage('TeacherAttendanceMessage', error.message, true);
        } finally {
            submit.disabled = false;
        }
    });

    historyAssignment.addEventListener('change', () => {
        clearAttendanceEdit();
        renderAttendanceHistory();
    });

    historyRows.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) return;
        const editButton = event.target.closest('[data-edit-attendance]');
        try {
            if (editButton) {
                const record = attendanceRecords.find((item) => String(item.id) === editButton.dataset.editAttendance);
                if (!record) throw new Error('No se encontró la asistencia seleccionada.');
                document.getElementById('TeacherEditAttendanceId').value = record.id;
                document.getElementById('TeacherEditAttendanceState').value = record.estado;
                document.getElementById('TeacherEditAttendanceNote').value = record.observacion || '';
                editAttendanceForm.hidden = false;
            }
        } catch (error) {
            showMessage('TeacherHistoryMessage', error.message, true);
        }
    });

    editAttendanceForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const record = attendanceRecords.find((item) =>
            String(item.id) === document.getElementById('TeacherEditAttendanceId').value);
        if (!record) {
            showMessage('TeacherHistoryMessage', 'No se encontró la asistencia seleccionada.', true);
            return;
        }

        try {
            const response = await api.put(`asistencias/${record.id}`, {
                cod_estudiante: Number(record.cod_estudiante),
                cod_asignacion: Number(record.cod_asignacion),
                cod_usuario_registro: Number(session.id),
                fecha: record.fecha,
                estado: document.getElementById('TeacherEditAttendanceState').value,
                observacion: document.getElementById('TeacherEditAttendanceNote').value.trim(),
            });
            if (response?.estado !== true) throw new Error(response?.message || 'No se pudo corregir la asistencia.');
            const refreshed = await api.get('asistencias');
            if (!Array.isArray(refreshed)) throw new Error('La asistencia se corrigió, pero no se pudo actualizar el historial.');
            attendanceRecords.splice(0, attendanceRecords.length, ...refreshed);
            clearAttendanceEdit();
            renderAttendanceHistory();
            showMessage('TeacherHistoryMessage', response.message);
        } catch (error) {
            showMessage('TeacherHistoryMessage', error.message, true);
        }
    });

    document.getElementById('CancelTeacherAttendanceEdit').addEventListener('click', clearAttendanceEdit);

    renderAssignments();
    refreshRoster();
    refreshAttendanceStudents();
    renderAttendanceHistory();
};

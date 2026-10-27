import { api } from '../utils/api.js';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const renderStudents = (students) => {
    const container = document.getElementById('StudentsTableList');

    if (!students.length) {
        container.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay estudiantes registrados.</td></tr>';
        return;
    }

    container.innerHTML = students.map((student) => `
        <tr class="transition hover:bg-slate-50">
            <td class="px-4 py-4 font-semibold text-slate-900">${escapeHtml(student.id)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(student.nombre)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(student.apellido)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(student.direccion)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(student.telefono)}</td>
        </tr>
    `).join('');
};

export const initStudentsList = async () => {
    const container = document.getElementById('StudentsTableList');
    const reloadButton = document.getElementById('ReloadStudents');
    const form = document.getElementById('StudentForm');
    const message = document.getElementById('StudentMessage');

    const loadStudents = async () => {
        container.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Cargando estudiantes...</td></tr>';

        try {
            const students = await api.get('estudiantes');
            if (!Array.isArray(students)) {
                throw new Error('La respuesta del API no contiene una lista de estudiantes.');
            }
            renderStudents(students);
        } catch (error) {
            container.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center font-semibold text-red-600">${escapeHtml(error.message)}</td></tr>`;
        }
    };

    reloadButton.addEventListener('click', loadStudents);
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const result = await api.post('estudiantes', payload);
            if (result?.estado !== true) throw new Error(result?.message || 'No se pudo registrar al estudiante.');
            form.reset();
            message.textContent = result.message || 'Estudiante registrado correctamente.';
            message.className = 'rounded-md bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700';
            await loadStudents();
        } catch (error) {
            message.textContent = error.message;
            message.className = 'rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700';
        } finally {
            submit.disabled = false;
        }
    });
    await loadStudents();
};

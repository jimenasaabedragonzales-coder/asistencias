import { api } from '../utils/api.js';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const showMessage = (message, isError = false) => {
    const element = document.getElementById('UsersMessage');
    element.textContent = message;
    element.className = `mb-4 rounded-lg px-4 py-3 text-sm font-semibold ${isError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`;
};

const clearForm = () => {
    document.getElementById('UserForm').reset();
    document.getElementById('UserId').value = '';
    document.getElementById('UserFormTitle').textContent = 'Nuevo usuario';
    const password = document.getElementById('UserPassword');
    password.required = true;
    password.placeholder = 'Contraseña para el usuario';
    document.getElementById('UserPasswordHint').textContent = 'Elige una contraseña que el usuario pueda recordar.';
    const role = document.getElementById('UserRole');
    role.querySelector('option[value="estudiante"]').disabled = true;
    role.value = 'admin';
    document.getElementById('UserTeacher').required = false;
};

const fillForm = (user) => {
    document.getElementById('UserId').value = user.id;
    document.getElementById('UserName').value = user.username;
    const password = document.getElementById('UserPassword');
    password.value = '';
    password.required = false;
    password.placeholder = 'Deja vacío para conservar la actual';
    document.getElementById('UserPasswordHint').textContent = 'Escribe una nueva contraseña solo si deseas cambiarla; si no, déjalo vacío.';
    const role = document.getElementById('UserRole');
    role.querySelector('option[value="estudiante"]').disabled = user.rol !== 'estudiante';
    role.value = user.rol || 'admin';
    document.getElementById('UserTeacher').value = user.id_docente ?? '';
    document.getElementById('UserTeacher').required = user.rol === 'docente';
    document.getElementById('UserStudent').value = user.id_estudiante ?? '';
    document.getElementById('UserFormTitle').textContent = 'Editar usuario';
};

const renderUsers = (users) => {
    const container = document.getElementById('UsersTableList');
    if (!users.length) {
        container.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay usuarios registrados.</td></tr>';
        return;
    }

    container.innerHTML = users.map((user) => `
        <tr class="transition hover:bg-slate-50">
            <td class="px-4 py-4 font-semibold text-slate-900">${escapeHtml(user.id)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(user.username)}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml({ admin: 'Administrador', docente: 'Docente', estudiante: 'Estudiante' }[user.rol] || 'Sin rol')}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(user.docente_nombre || 'No aplica')}</td>
            <td class="px-4 py-4 text-slate-700">${escapeHtml(user.id_estudiante || 'No aplica')}</td>
            <td class="px-4 py-4 text-right">
                <button data-edit-user="${escapeHtml(user.id)}" class="mr-2 rounded-md px-3 py-2 text-sm font-semibold text-cyan-700 hover:bg-cyan-50">Editar</button>
                <button data-delete-user="${escapeHtml(user.id)}" class="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Eliminar</button>
            </td>
        </tr>
    `).join('');
};

export const loadUsers = async () => {
    const container = document.getElementById('UsersTableList');
    container.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Cargando usuarios...</td></tr>';
    try {
        const users = await api.get('users');
        if (!Array.isArray(users)) {
            throw new Error('La respuesta del API no contiene una lista de usuarios.');
        }
        renderUsers(users);
    } catch (error) {
        container.innerHTML = `<tr><td colspan="6" class="px-4 py-6 text-center font-semibold text-red-600">${escapeHtml(error.message)}</td></tr>`;
    }
};

const loadTeachers = async () => {
    const select = document.getElementById('UserTeacher');
    const teachers = await api.get('docentes');
    if (!Array.isArray(teachers)) {
        throw new Error('La respuesta del API no contiene una lista de docentes.');
    }

    select.innerHTML = '<option value="">Selecciona un docente</option>' + teachers.map((teacher) => `
        <option value="${escapeHtml(teacher.id)}">${escapeHtml(`${teacher.nombre} ${teacher.apellido}`)} (ID ${escapeHtml(teacher.id)})</option>
    `).join('');
};

export const initUsersCrud = async () => {
    const roleSelect = document.getElementById('UserRole');
    const teacherSelect = document.getElementById('UserTeacher');
    roleSelect.addEventListener('change', () => {
        teacherSelect.required = roleSelect.value === 'docente';
    });

    document.getElementById('UserForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = document.getElementById('UserId').value;
        const payload = {
            username: document.getElementById('UserName').value,
            password: document.getElementById('UserPassword').value,
            rol: roleSelect.value,
            id_docente: teacherSelect.value || null,
            id_estudiante: document.getElementById('UserStudent').value || null,
        };
        if (!payload.password && id) delete payload.password;

        try {
            const response = id ? await api.put(`users/${id}`, payload) : await api.post('users', payload);
            if (response.estado !== true) throw new Error(response.message || 'No se pudo guardar el usuario');
            showMessage(response.message);
            clearForm();
            await loadUsers();
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    document.getElementById('CancelUserEdit').addEventListener('click', clearForm);
    document.getElementById('UsersTableList').addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) return;
        const editButton = event.target.closest('[data-edit-user]');
        const deleteButton = event.target.closest('[data-delete-user]');

        try {
            if (editButton) {
                fillForm(await api.get(`users/${editButton.dataset.editUser}`));
            }
            if (deleteButton && window.confirm('¿Deseas eliminar este usuario?')) {
                const response = await api.del(`users/${deleteButton.dataset.deleteUser}`);
                if (response.estado !== true) throw new Error(response.message || 'No se pudo eliminar el usuario');
                showMessage(response.message);
                await loadUsers();
            }
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    await loadUsers();
    try {
        await loadTeachers();
    } catch (error) {
        showMessage(error.message, true);
    }
};

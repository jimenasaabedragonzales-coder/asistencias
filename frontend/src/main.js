import { initUsersCrud } from './components/UsersList.js';
import { initStudentsList } from './components/StudentsList.js';
import { initAdminResource } from './components/AdminResources.js?v=20261023-docentes-edit-fix-v1';
import { initTeacherPanel } from './components/TeacherPanel.js?v=20261022-catalog-cleanup-v1';
import { initLogin } from './components/Login.js';
import { mountIcons } from './utils/icons.js';
import { clearSession, getSession } from './utils/session.js';
import { api } from './utils/api.js?v=20261022-catalog-cleanup-v1';

const app = document.getElementById('app');
const pageTitle = document.getElementById('pageTitle');
const appShell = document.getElementById('appShell');
const loginRoot = document.getElementById('loginRoot');
const roleLabel = document.getElementById('roleLabel');
const currentUser = document.getElementById('currentUser');
let session = null;

const titles = {
    home: 'Dashboard',
    users: 'Usuarios',
    students: 'Estudiantes',
    teacher: 'Mis materias y cursos',
};

const setActiveLink = (view, resource) => {
    document.querySelectorAll('.nav-link[data-view]').forEach((link) => {
        const isActive = link.dataset.view === view
            && (view !== 'resource' || link.dataset.resource === resource);
        link.classList.toggle('bg-cyan-700', isActive);
        link.classList.toggle('text-white', isActive);
        link.classList.toggle('text-slate-200', !isActive);
    });
};

const loadView = async (view, file, title, resource = null) => {
    const response = await fetch(`./src/views/${file}.html?v=20261022-catalog-cleanup-v1`);
    if (!response.ok) throw new Error(`No se pudo cargar la página (${response.status}).`);

    app.innerHTML = await response.text();
    mountIcons(app);
    const resourceTitle = document.querySelector(`[data-view="resource"][data-resource="${resource}"]`)?.textContent.trim();
    pageTitle.textContent = view === 'resource' ? resourceTitle || 'Administración' : title;
    setActiveLink(view, resource);

    if (view === 'users') await initUsersCrud();
    if (view === 'students') await initStudentsList();
    if (view === 'teacher') await initTeacherPanel(session);
    if (view === 'resource') await initAdminResource(resource, session);
    if (view === 'home') await loadDashboardStats();
};

const views = {
    home: () => loadView('home', 'home', titles.home),
    users: () => loadView('users', 'users', titles.users),
    students: () => loadView('students', 'students', titles.students),
    teacher: () => loadView('teacher', 'teacher', titles.teacher),
    resource: (resource) => loadView('resource', 'resource', '', resource),
};

const localDate = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
};

const loadDashboardStats = async () => {
    const [attendance, students, teachers, courses] = await Promise.all([
        api.get('asistencias'),
        api.get('estudiantes'),
        api.get('docentes'),
        api.get('cursos'),
    ]);
    const lists = { attendance, students, teachers, courses };
    for (const [name, value] of Object.entries(lists)) {
        if (!Array.isArray(value)) throw new Error(`No se pudo leer la lista de ${name}.`);
    }
    const totals = {
        asistencias: attendance.filter((record) => record.fecha === localDate()).length,
        estudiantes: students.length,
        docentes: teachers.length,
        cursos: courses.length,
    };
    for (const [name, total] of Object.entries(totals)) {
        const stat = document.querySelector(`[data-dashboard-stat="${name}"]`);
        if (stat) stat.textContent = String(total);
    }
};

const showWorkspace = async (user) => {
    if (!user || !['admin', 'docente'].includes(user.rol)) {
        clearSession();
        showLogin();
        return;
    }

    session = user;
    loginRoot.hidden = true;
    appShell.hidden = false;
    roleLabel.textContent = user.rol === 'admin' ? 'Panel administrativo' : 'Panel docente';
    currentUser.textContent = `${user.username} · ${user.rol === 'admin' ? 'Administrador' : 'Docente'}`;
    document.querySelectorAll('[data-role]').forEach((element) => {
        element.hidden = element.dataset.role !== user.rol;
    });
    mountIcons(appShell);
    try {
        await (user.rol === 'admin' ? views.home() : views.teacher());
    } catch (error) {
        console.error('Error al cargar el panel:', error);
        pageTitle.textContent = 'Error';
        app.innerHTML = '<p class="rounded-lg bg-red-50 p-4 font-semibold text-red-700">No se pudo cargar el panel. Revisa la conexión y vuelve a intentarlo.</p>';
    }
};

function showLogin() {
    appShell.hidden = true;
    loginRoot.hidden = false;
    fetch('./src/views/login.html')
        .then((response) => {
            if (!response.ok) throw new Error(`No se pudo cargar el login (${response.status}).`);
            return response.text();
        })
        .then((html) => {
            loginRoot.innerHTML = html;
            mountIcons(loginRoot);
            initLogin(showWorkspace);
        })
        .catch((error) => {
            console.error('Error al cargar el login:', error);
            loginRoot.innerHTML = '<p class="m-6 rounded-lg bg-red-50 p-4 font-semibold text-red-700">No se pudo cargar el inicio de sesión.</p>';
        });
}

document.addEventListener('click', async (event) => {
    if (!(event.target instanceof Element)) return;
    const link = event.target.closest('[data-view]');
    const view = link?.dataset.view;

    if (!link || !views[view]) return;
    event.preventDefault();
    if (link.dataset.role && link.dataset.role !== session?.rol) return;

    try {
        await views[view](link.dataset.resource);
    } catch (error) {
        console.error('Error al cargar la página:', error);
        pageTitle.textContent = 'Error';
        app.innerHTML = '<p class="rounded-lg bg-red-50 p-4 font-semibold text-red-700">No se pudo cargar esta página. Revisa la conexión y vuelve a intentarlo.</p>';
    }
});

document.getElementById('logoutButton').addEventListener('click', async () => {
    try {
        await api.post('auth/logout', {});
    } catch (error) {
        console.error('No se pudo cerrar la sesión en el servidor:', error);
    } finally {
        clearSession();
        session = null;
        showLogin();
    }
});

const existingSession = getSession();
if (existingSession) {
    showWorkspace(existingSession).catch((error) => {
        console.error('Error al cargar el panel:', error);
        app.innerHTML = '<p class="rounded-lg bg-red-50 p-4 font-semibold text-red-700">No se pudo cargar el panel. Revisa la conexión y vuelve a intentarlo.</p>';
    });
} else {
    showLogin();
}

import { api } from '../utils/api.js?v=20261022-catalog-cleanup-v1';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const findById = (collection, id) => (Array.isArray(collection) ? collection : [])
    .find((item) => Number(item.id) === Number(id));

const resources = {
    estudiantes: {
        title: 'Estudiantes',
        description: 'Crear, editar y eliminar estudiantes registrados.',
        fields: [
            { name: 'nombre', label: 'Nombre', required: true },
            { name: 'apellido', label: 'Apellido', required: true },
            { name: 'direccion', label: 'Dirección', required: true },
            { name: 'telefono', label: 'Teléfono', required: true },
        ],
    },
    docentes: {
        title: 'Docentes',
        description: 'Cada docente se muestra una sola vez. Al editarlo puedes consultar sus materias y cursos asignados.',
        dependencies: ['asignaciones', 'materias', 'cursos'],
        fields: [
            { name: 'nombre', label: 'Nombre', required: true },
            { name: 'apellido', label: 'Apellido', required: true },
            { name: 'telefono', label: 'Teléfono' },
        ],
    },
    materias: {
        title: 'Materias',
        description: 'Administrar el catálogo de materias del sistema.',
        fields: [{ name: 'nombre_materia', label: 'Nombre de la materia', required: true }],
    },
    cursos: {
        title: 'Cursos',
        description: 'Cada curso y paralelo se muestra una sola vez. Al editar puedes revisar sus asignaciones e inscripciones.',
        dependencies: ['asignaciones', 'inscripciones', 'docentes', 'materias', 'estudiantes'],
        fields: [
            { name: 'nombre_curso', label: 'Nombre del curso', required: true },
            { name: 'nivel', label: 'Nivel', required: true },
            { name: 'paralelo', label: 'Paralelo', required: true },
        ],
    },
    asignaciones: {
        title: 'Asignaciones',
        description: 'Vincular docentes con materias y cursos.',
        fields: [
            { name: 'cod_docente', label: 'Docente', options: 'docentes', display: (item) => `${item.nombre} ${item.apellido}` },
            { name: 'cod_materia', label: 'Materia', options: 'materias', display: (item) => item.nombre_materia },
            { name: 'cod_curso', label: 'Curso', options: 'cursos', display: (item) => `${item.nombre_curso} · ${item.nivel} ${item.paralelo}` },
        ],
    },
    inscripciones: {
        title: 'Inscripciones',
        description: 'Asignar estudiantes a cursos y gestiones.',
        fields: [
            { name: 'cod_estudiante', label: 'Estudiante', options: 'estudiantes', display: (item) => `${item.nombre} ${item.apellido}` },
            { name: 'cod_curso', label: 'Curso', options: 'cursos', display: (item) => `${item.nombre_curso} · ${item.nivel} ${item.paralelo}` },
            { name: 'gestion', label: 'Gestión', type: 'number', required: true },
        ],
    },
    asistencias: {
        title: 'Asistencias',
        description: 'Consultar, registrar y corregir asistencias.',
        dependencies: ['materias', 'cursos'],
        fields: [
            { name: 'cod_estudiante', label: 'Estudiante', options: 'estudiantes', display: (item) => `${item.nombre} ${item.apellido}` },
            { name: 'cod_asignacion', label: 'Asignación', options: 'asignaciones', display: (item, data) => {
                const subject = findById(data.materias, item.cod_materia);
                const course = findById(data.cursos, item.cod_curso);
                return `${subject?.nombre_materia || item.cod_materia} · ${course?.nombre_curso || item.cod_curso}`;
            } },
            { name: 'fecha', label: 'Fecha', type: 'date', required: true },
            { name: 'estado', label: 'Estado', values: ['Presente', 'Ausente', 'Licencia', 'Retraso'] },
            { name: 'observacion', label: 'Observación', required: true },
        ],
    },
};

const optionEndpoints = {
    docentes: 'docentes',
    materias: 'materias',
    cursos: 'cursos',
    estudiantes: 'estudiantes',
    asignaciones: 'asignaciones',
    inscripciones: 'inscripciones',
};

const showMessage = (message, isError = false) => {
    const element = document.getElementById('ResourceMessage');
    element.textContent = message;
    element.className = `mt-4 rounded-md px-4 py-3 text-sm font-semibold ${isError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`;
};

export const initAdminResource = async (resourceName, session) => {
    const config = resources[resourceName];
    if (!config) throw new Error('El módulo solicitado no existe.');

    const title = document.getElementById('ResourceTitle');
    const description = document.getElementById('ResourceDescription');
    const form = document.getElementById('ResourceForm');
    const head = document.getElementById('ResourceHead');
    const rows = document.getElementById('ResourceRows');
    const data = {};
    let teacherGroups = new Map();

    title.textContent = config.title;
    description.textContent = config.description;

    const requiredOptions = [
        ...config.fields.filter((field) => field.options).map((field) => field.options),
        ...(config.dependencies || []),
    ];
    for (const endpoint of new Set(requiredOptions)) {
        const values = await api.get(optionEndpoints[endpoint]);
        if (!Array.isArray(values)) throw new Error(`El API no devolvió una lista válida para ${endpoint}.`);
        data[endpoint] = values;
    }

    const renderControl = (field) => {
        if (field.options) {
            const options = (data[field.options] || []).map((item) => {
                const label = field.display ? field.display(item, data) : item.id;
                return `<option value="${escapeHtml(item.id)}">${escapeHtml(label)}</option>`;
            }).join('');
            return `<select id="ResourceField-${field.name}" name="${field.name}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-normal"><option value="">Selecciona una opción</option>${options}</select>`;
        }
        if (field.values) {
            return `<select id="ResourceField-${field.name}" name="${field.name}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-normal">${field.values.map((value) => `<option>${escapeHtml(value)}</option>`).join('')}</select>`;
        }
        return `<input id="ResourceField-${field.name}" name="${field.name}" type="${field.type || 'text'}" ${field.required ? 'required' : ''} class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-normal">`;
    };

    form.innerHTML = `<input id="ResourceId" type="hidden">${config.fields.map((field) => `
        <label class="text-sm font-semibold text-slate-700">${escapeHtml(field.label)}
            ${renderControl(field)}
        </label>
    `).join('')}
        ${['docentes', 'cursos'].includes(resourceName) ? `<section id="ResourceRelations" class="col-span-full hidden rounded-md border border-slate-200 bg-slate-50 p-4"><h3 class="text-sm font-bold text-slate-800">${resourceName === 'docentes' ? 'Materias y cursos relacionados' : 'Asignaciones e inscripciones relacionadas'}</h3><div class="mt-2 text-sm text-slate-600"></div></section>` : ''}
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Guardar</button>
            <button id="ResourceCancel" type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpiar</button>
        </div>`;

    head.innerHTML = `<tr><th class="px-4 py-3">ID</th>${config.fields.map((field) => `<th class="px-4 py-3">${escapeHtml(field.label)}</th>`).join('')}<th class="px-4 py-3 text-right">Acciones</th></tr>`;

    const displayValue = (field, item) => {
        if (!field.options) return item[field.name];
        const entry = (data[field.options] || []).find((option) => Number(option.id) === Number(item[field.name]));
        return field.display && entry ? field.display(entry, data) : (entry?.id ?? item[field.name]);
    };

    const loadRows = async () => {
        rows.innerHTML = `<tr><td colspan="${config.fields.length + 2}" class="px-4 py-6 text-center text-slate-500">Cargando...</td></tr>`;
        const items = await api.get(resourceName);
        if (!Array.isArray(items)) throw new Error(`El API no devolvió una lista válida de ${config.title.toLowerCase()}.`);
        // The relation panel can look up records from the resource being edited too.
        data[resourceName] = items;
        let visibleItems = items;
        if (resourceName === 'docentes' || resourceName === 'cursos') {
            const identityKey = (item) => (resourceName === 'docentes'
                ? [item.nombre, item.apellido, item.telefono]
                : [item.nombre_curso, item.nivel, item.paralelo])
                .map((value) => String(value ?? '').trim().toLocaleLowerCase())
                .join('|');
            const groups = new Map();
            for (const item of items) {
                const key = identityKey(item);
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(item);
            }
            if (resourceName === 'docentes') teacherGroups = groups;
            else teacherGroups = new Map([...teacherGroups, ...groups]);
            // Keep the record with the most linked history visible; preserve the lowest ID on ties.
            visibleItems = [...groups.values()].map((group) => group.sort((a, b) => {
                const count = (record) => resourceName === 'docentes'
                    ? (data.asignaciones || []).filter((row) => Number(row.cod_docente) === Number(record.id)).length
                    : (data.asignaciones || []).filter((row) => Number(row.cod_curso) === Number(record.id)).length
                        + (data.inscripciones || []).filter((row) => Number(row.cod_curso) === Number(record.id)).length;
                return count(b) - count(a) || Number(a.id) - Number(b.id);
            })[0]);
        }
        rows.innerHTML = visibleItems.length ? visibleItems.map((item) => `
            <tr>
                <td class="px-4 py-3 font-semibold">${escapeHtml(item.id)}</td>
                ${config.fields.map((field) => `<td class="px-4 py-3">${escapeHtml(displayValue(field, item))}</td>`).join('')}
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <button type="button" data-edit-resource="${escapeHtml(item.id)}" class="mr-2 rounded px-2 py-1 font-semibold text-cyan-700 hover:bg-cyan-50">Editar</button>
                    <button type="button" data-delete-resource="${escapeHtml(item.id)}" class="rounded px-2 py-1 font-semibold text-red-700 hover:bg-red-50">Eliminar</button>
                </td>
            </tr>
        `).join('') : `<tr><td colspan="${config.fields.length + 2}" class="px-4 py-6 text-center text-slate-500">No hay registros.</td></tr>`;
        return items;
    };

    const clearForm = () => {
        form.reset();
        document.getElementById('ResourceId').value = '';
        document.getElementById('ResourceRelations')?.classList.add('hidden');
    };
    let items = [];

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = document.getElementById('ResourceId').value;
        const payload = Object.fromEntries(new FormData(form).entries());
        for (const field of config.fields) {
            if (field.type === 'number' || field.options) payload[field.name] = Number(payload[field.name]);
        }
        if (resourceName === 'asistencias') {
            const existingItem = items.find((item) => String(item.id) === id);
            payload.cod_usuario_registro = existingItem?.cod_usuario_registro ?? session.id;
        }

        try {
            const result = id ? await api.put(`${resourceName}/${id}`, payload) : await api.post(resourceName, payload);
            if (result?.estado !== true) throw new Error(result?.message || 'No se pudo guardar el registro.');
            clearForm();
            showMessage(result.message);
            items = await loadRows();
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    document.getElementById('ResourceCancel').addEventListener('click', clearForm);
    rows.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) return;
        const editButton = event.target.closest('[data-edit-resource]');
        const deleteButton = event.target.closest('[data-delete-resource]');
        try {
            if (editButton) {
                const item = items.find((value) => String(value.id) === editButton.dataset.editResource);
                if (!item) throw new Error('No se encontró el registro seleccionado.');
                document.getElementById('ResourceId').value = item.id;
                for (const field of config.fields) {
                    const input = form.elements.namedItem(field.name);
                    input.value = item[field.name] ?? '';
                }
                if (resourceName === 'docentes' || resourceName === 'cursos') {
                    const relationPanel = document.getElementById('ResourceRelations');
                    const relationList = relationPanel.querySelector('div');
                    const identityKey = (resourceName === 'docentes'
                        ? [item.nombre, item.apellido, item.telefono]
                        : [item.nombre_curso, item.nivel, item.paralelo])
                        .map((value) => String(value ?? '').trim().toLocaleLowerCase())
                        .join('|');
                    const duplicateIds = new Set((teacherGroups.get(identityKey) || [item]).map((teacher) => Number(teacher.id)));
                    const assignments = (data.asignaciones || []).filter((row) => duplicateIds.has(Number(row[resourceName === 'docentes' ? 'cod_docente' : 'cod_curso'])));
                    const enrollments = resourceName === 'cursos'
                        ? (data.inscripciones || []).filter((row) => duplicateIds.has(Number(row.cod_curso)))
                        : [];
                    const assignmentHtml = assignments.map((assignment) => {
                        const subject = findById(data.materias, assignment.cod_materia);
                        const course = findById(data.cursos, assignment.cod_curso);
                        const teacher = findById(data.docentes, assignment.cod_docente);
                        const subjectName = subject?.nombre_materia || `Materia #${assignment.cod_materia}`;
                        const courseName = course ? `${course.nombre_curso} · ${course.nivel} ${course.paralelo}` : `Curso #${assignment.cod_curso}`;
                        const teacherName = teacher ? `${teacher.nombre} ${teacher.apellido}` : `Docente #${assignment.cod_docente}`;
                        const label = resourceName === 'docentes' ? `${subjectName} — ${courseName}` : `${teacherName} — ${subjectName}`;
                        return `<li>${escapeHtml(label)}${duplicateIds.size > 1 ? ` · registro #${resourceName === 'docentes' ? assignment.cod_docente : assignment.cod_curso}` : ''}</li>`;
                    });
                    const enrollmentHtml = enrollments.map((enrollment) => {
                        const student = findById(data.estudiantes, enrollment.cod_estudiante);
                        const studentName = student ? `${student.nombre} ${student.apellido}` : `Estudiante #${enrollment.cod_estudiante}`;
                        return `<li>${escapeHtml(studentName)} — Gestión ${escapeHtml(enrollment.gestion)}</li>`;
                    });
                    const relationItems = [...assignmentHtml, ...enrollmentHtml];
                    relationList.innerHTML = relationItems.length ? `<ul class="list-disc space-y-1 pl-5">${relationItems.join('')}</ul>` : `<p>${resourceName === 'docentes' ? 'Este docente' : 'Este curso'} no tiene asignaciones ni inscripciones registradas.</p>`;
                    if (duplicateIds.size > 1) {
                        relationList.insertAdjacentHTML('afterbegin', `<p class="mb-2">Se agruparon ${duplicateIds.size} registros con los mismos datos. Se muestran todas sus relaciones.</p>`);
                    }
                    relationPanel.classList.remove('hidden');
                    relationPanel.classList.add('col-span-full');
                }
            } else if (deleteButton && window.confirm('¿Deseas eliminar este registro?')) {
                const result = await api.del(`${resourceName}/${deleteButton.dataset.deleteResource}`);
                if (result?.estado !== true) throw new Error(result?.message || 'No se pudo eliminar el registro.');
                showMessage(result.message);
                items = await loadRows();
            }
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    items = await loadRows();
};

const storageKey = 'control-asistencias.user';
const supportedRoles = new Set(['admin', 'docente']);

export const getSession = () => {
    try {
        const session = JSON.parse(localStorage.getItem(storageKey) || 'null');
        if (!session || !supportedRoles.has(session.rol) || !session.id) return null;
        return session;
    } catch (error) {
        localStorage.removeItem(storageKey);
        return null;
    }
};

export const saveSession = (user) => {
    if (!user || !supportedRoles.has(user.rol) || !user.id) {
        throw new Error('La respuesta de inicio de sesión no contiene un usuario válido.');
    }

    const session = {
        id: Number(user.id),
        username: String(user.username),
        id_docente: user.id_docente ? Number(user.id_docente) : null,
        id_estudiante: user.id_estudiante ? Number(user.id_estudiante) : null,
        rol: user.rol,
    };
    localStorage.setItem(storageKey, JSON.stringify(session));
    return session;
};

export const clearSession = () => localStorage.removeItem(storageKey);

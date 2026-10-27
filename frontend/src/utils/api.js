const apiUrl = new URL('../../../backend/', import.meta.url);

const buildUrl = (endpoint) => new URL(String(endpoint).replace(/^\/+/, ''), apiUrl);

const request = async (endpoint, options = {}) => {
    let response;

    try {
        response = await fetch(buildUrl(endpoint), {
            ...options,
            credentials: 'same-origin',
        });
    } catch (error) {
        if (error instanceof TypeError) {
            throw new Error('No se pudo comunicar con el API. Verifica que WAMP esté activo y que el servidor permita solicitudes desde el frontend.');
        }
        throw error;
    }

    const responseText = await response.text();
    let data = null;

    if (responseText) {
        try {
            data = JSON.parse(responseText);
        } catch {
            if (response.ok) {
                throw new Error('El API devolvió una respuesta que no es JSON válido.');
            }
        }
    }

    if (!response.ok) {
        throw new Error(data?.message || data?.error || `Error HTTP: ${response.status}`);
    }

    return data;
};

export const api = {
    get: (endpoint) => request(endpoint),

    post: (endpoint, payload) => request(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    }),

    put: (endpoint, payload) => request(endpoint, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    }),

    del: (endpoint) => request(endpoint, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
    }),
};

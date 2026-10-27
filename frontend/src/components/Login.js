import { api } from '../utils/api.js';
import { saveSession } from '../utils/session.js';

export const initLogin = (onAuthenticated) => {
    const form = document.getElementById('LoginForm');
    const message = document.getElementById('LoginMessage');
    const submit = document.getElementById('LoginSubmit');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.classList.add('hidden');
        submit.disabled = true;
        submit.textContent = 'Verificando...';

        try {
            const response = await api.post('auth/login', {
                username: document.getElementById('LoginUsername').value.trim(),
                password: document.getElementById('LoginPassword').value,
            });
            const user = saveSession(response?.usuario);
            form.reset();
            await onAuthenticated(user);
        } catch (error) {
            message.textContent = error.message || 'No se pudo iniciar sesión.';
            message.className = 'mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700';
        } finally {
            submit.disabled = false;
            submit.textContent = 'Entrar';
        }
    });
};

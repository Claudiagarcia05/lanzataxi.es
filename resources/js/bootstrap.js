// Configuración global de Axios para el frontend
// - Headers por defecto para peticiones AJAX
// - CSRF token (si existe en el DOM)
// - Bearer token (si existe en localStorage)
// - Interceptor para manejar expiración / sesión no válida (401)
import axios from 'axios';
window.axios = axios;

// Headers comunes para peticiones XHR/JSON
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

// CSRF: se toma del meta tag generado por Laravel (si está presente)
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Token JWT guardado en localStorage (si el usuario ya inició sesión anteriormente)
const token = localStorage.getItem('token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Interceptor de respuestas:
// - Si llega un 401, se limpia la sesión local y se redirige a /login (salvo en endpoints de auth)
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            const requestUrl = error.config?.url || '';
            // Evitar bucles: si el 401 viene de login/register/me/logout, no forzamos redirección
            const isAuthRequest =
                requestUrl.includes('/api/login') ||
                requestUrl.includes('/api/register') ||
                requestUrl.includes('/api/me') ||
                requestUrl.includes('/api/logout');

            if (!isAuthRequest) {
                const hadToken = !!localStorage.getItem('token');

                // Limpieza de credenciales locales
                localStorage.removeItem('token');
                localStorage.removeItem('usuario');
                delete window.axios.defaults.headers.common['Authorization'];
                document.cookie = 'token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';

                // Redirección a login si no estamos ya en login/registro
                if (
                    hadToken &&
                    !window.location.pathname.includes('login') &&
                    !window.location.pathname.includes('register')
                ) {
                    window.location.href = '/login';
                }
            }
        }
        
        return Promise.reject(error);
    }
);
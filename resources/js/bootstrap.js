// Bootstrap del cliente HTTP (Axios).
// Centraliza cabeceras por defecto, CSRF/Sanctum, token Bearer y un interceptor de 401.
import axios from 'axios';
window.axios = axios;

// Necesario para que Sanctum/XSRF funcione con cookies.
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    // Para formularios/requests que dependan del token CSRF tradicional de Laravel.
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Inicializa la cookie XSRF para Sanctum (si la app lo usa). Si falla, no bloqueamos:
// la app puede seguir funcionando con Bearer token o en modo invitado.
window.axios.get('/sanctum/csrf-cookie').catch(() => {});

const token = localStorage.getItem('token');
if (token) {
    // Si el usuario ya había iniciado sesión, rehidratamos el Bearer token.
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

window.axios.interceptors.response.use(
    response => response,
    error => {
        // Si el backend responde 401 (no autenticado), limpiamos el estado local.
        // Importante: evitamos bucles/ruido si el 401 viene de endpoints de auth.
        if (error.response?.status === 401) {
            const requestUrl = error.config?.url || '';
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

                if (
                    hadToken &&
                    !window.location.pathname.includes('login') &&
                    !window.location.pathname.includes('register')
                ) {
                    // Redirección dura para forzar un estado consistente cuando expira sesión.
                    window.location.href = '/login';
                }
            }
        }
        
        return Promise.reject(error);
    }
);
// Store/composable de autenticación (frontend)
// - Gestiona usuario y token
// - Realiza login y registro contra la API
// - Calcula la ruta de dashboard según el rol
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

export function useAuthStore() {
  // Estado reactivo principal
  const user = ref(null);
  const token = ref(null);

  function getDashboardRoute() {
    // Devuelve a qué dashboard redirigir según el rol
    if (!user.value) return '/';
    switch (user.value.role) {
      case 'conductor':

        return '/conductor/dashboard';
      case 'admin':

        return '/admin/dashboard';
      default:

        return '/pasajero/home';
    }
  }

  async function login({ email, password }) {
    // Login: solicita token a la API y lo guarda localmente
    try {
      const response = await axios.post('/api/login', { email, password });
      if (response.data && response.data.token) {
        token.value = response.data.token;
        user.value = response.data.user;

        // Persistencia del token en el navegador
        localStorage.setItem('token', token.value);

        // Configura axios para enviar el Bearer token en siguientes peticiones
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (error) {
      if (error.response && error.response.status === 401) {
        // Credenciales incorrectas
        
        return {
          success: false,
          error: error.response.data.message || 'Credenciales inválidas. Por favor verifica tu email y contraseña.'
        };
      }

      // Errores generales (red, validaciones u otros)
      return {
        success: false,
        error: error.response?.data?.message || 'Error al intentar iniciar sesión.'
      };
    }
  }

  async function register({ name, email, phone, password, password_confirmation, role }) {
    // Registro: validación mínima de email antes de llamar a la API
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!re.test(email)) {

      return { success: false, error: 'Email no válido' };
    }
    // Regla de negocio: coherencia entre rol Admin y dominio @admin.com
    const normalizedEmail = String(email || '').trim().toLowerCase();
    const normalizedRole = role || 'pasajero';
    const isAdminEmail = normalizedEmail.endsWith('@admin.com');

    const mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

    if (normalizedRole === 'admin' && !isAdminEmail) {
      return { success: false, error: mensajeGenericoCredenciales };
    }

    if (normalizedRole !== 'admin' && isAdminEmail) {
      return { success: false, error: mensajeGenericoCredenciales };
    }
    try {
      const response = await axios.post('/api/register', {
        name,
        email,
        phone,
        password,
        password_confirmation,
        role
      });
      if (response.data && response.data.token) {
        token.value = response.data.token;
        user.value = response.data.user;

        // Persistencia del token en el navegador
        localStorage.setItem('token', token.value);

        // Configura axios para enviar el Bearer token en siguientes peticiones
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (error) {
      // Devuelve mensaje y posibles errores de validación de backend

      return {
        success: false,
        error: error.response?.data?.message || 'Error al intentar registrar usuario',
        errors: error.response?.data?.errors || {}
      };
    }
  }

  // API pública del store/composable
  return {
    user,
    token,
    login,
    register,
    getDashboardRoute
  };
}
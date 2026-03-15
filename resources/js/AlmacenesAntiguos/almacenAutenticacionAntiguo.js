// Store/composable de autenticación (frontend)
// - Gestiona usuario y token
// - Realiza login y registro contra la API
// - Calcula la ruta de dashboard según el rol
import { ref } from 'vue';
import axios from 'axios';

export function useAuthStore() {
  // Estado reactivo principal
  const usuario = ref(null);
  const tokenAcceso = ref(null);

  function getDashboardRoute() {
    // Devuelve a qué dashboard redirigir según el rol
    if (!usuario.value) return '/';
    switch (usuario.value.role) {
      case 'conductor':

        return '/conductor/dashboard';
      case 'admin':

        return '/administradir/home';
      default:

        return '/pasajero/home';
    }
  }

  async function login({ email, password }) {
    // Login: solicita token a la API y lo guarda localmente
    try {
      const respuesta = await axios.post('/api/login', { email, password });
      if (respuesta.data && respuesta.data.token) {
        tokenAcceso.value = respuesta.data.token;
        usuario.value = respuesta.data.user;

        // Persistencia del token en el navegador
        localStorage.setItem('token', tokenAcceso.value);

        // Configura axios para enviar el Bearer token en siguientes peticiones
        axios.defaults.headers.common['Authorization'] = `Bearer ${tokenAcceso.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (errorCapturado) {
      if (errorCapturado.response && errorCapturado.response.status === 401) {
        // Credenciales incorrectas
        
        return {
          success: false,
          error: errorCapturado.response.data.message || 'Credenciales inválidas. Por favor verifica tu email y contraseña.'
        };
      }

      // Errores generales (red, validaciones u otros)
      return {
        success: false,
        error: errorCapturado.response?.data?.message || 'Error al intentar iniciar sesión.'
      };
    }
  }

  async function register({ name, email, phone, password, password_confirmation, role }) {
    // Registro: validación mínima de email antes de llamar a la API
    const expresionCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!expresionCorreo.test(email)) {

      return { success: false, error: 'Email no válido' };
    }
    // Regla de negocio: coherencia entre rol Admin y dominio @admin.com
    const correoNormalizado = String(email || '').trim().toLowerCase();
    const rolNormalizado = role || 'pasajero';
    const esCorreoAdmin = correoNormalizado.endsWith('@admin.com');

    const mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

    if (rolNormalizado === 'admin' && !esCorreoAdmin) {
      return { success: false, error: mensajeGenericoCredenciales };
    }

    if (rolNormalizado !== 'admin' && esCorreoAdmin) {
      return { success: false, error: mensajeGenericoCredenciales };
    }
    try {
      const respuesta = await axios.post('/api/register', {
        name,
        email,
        phone,
        password,
        password_confirmation,
        role
      });
      if (respuesta.data && respuesta.data.token) {
        tokenAcceso.value = respuesta.data.token;
        usuario.value = respuesta.data.user;

        // Persistencia del token en el navegador
        localStorage.setItem('token', tokenAcceso.value);

        // Configura axios para enviar el Bearer token en siguientes peticiones
        axios.defaults.headers.common['Authorization'] = `Bearer ${tokenAcceso.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (errorCapturado) {
      // Devuelve mensaje y posibles errores de validación de backend

      return {
        success: false,
        error: errorCapturado.response?.data?.message || 'Error al intentar registrar usuario',
        errors: errorCapturado.response?.data?.errors || {}
      };
    }
  }

  // API pública del store/composable
  return {
    user: usuario,
    token: tokenAcceso,
    login,
    register,
    getDashboardRoute
  };
}
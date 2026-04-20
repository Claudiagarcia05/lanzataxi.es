import { ref } from 'vue';
import axios from 'axios';

/**
 * Store de autenticación (LEGACY / antiguo).
 *
 * Este archivo se conserva por compatibilidad histórica o referencia.
 * En el código actual la autenticación principal suele estar en un store Pinia
 * (ver `resources/js/Almacenes/almacenAutenticacion.js`).
 *
 * Responsabilidades:
 * - Login/registro contra la API
 * - Guardar token en localStorage
 * - Configurar el header global de axios `Authorization: Bearer <token>`
 */
export function useAuthStore() {
  const usuario = ref(null);
  const tokenAcceso = ref(null);

  function getDashboardRoute() {
    // Determina la ruta principal tras autenticación según rol.
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
    // Login básico: si el backend devuelve token, lo persistimos.
    try {
      const respuesta = await axios.post('/api/login', { email, password });
      if (respuesta.data && respuesta.data.token) {
        tokenAcceso.value = respuesta.data.token;
        usuario.value = respuesta.data.user;

        // Persistencia simple del token para recargar sesión en el navegador.
        localStorage.setItem('token', tokenAcceso.value);

        // Header global para autenticar llamadas posteriores.
        axios.defaults.headers.common['Authorization'] = `Bearer ${tokenAcceso.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (errorCapturado) {
      if (errorCapturado.response && errorCapturado.response.status === 401) {
        // 401: credenciales inválidas.
        
        return {
          success: false,
          error: errorCapturado.response.data.message || 'Credenciales inválidas. Por favor verifica tu email y contraseña.'
        };
      }

      return {
        success: false,
        error: errorCapturado.response?.data?.message || 'Error al intentar iniciar sesión.'
      };
    }
  }

  async function register({ name, email, phone, password, password_confirmation, role }) {
    // Registro: incluye validación básica de formato email y una regla legacy
    // para limitar el rol admin al dominio @admin.es.
    const expresionCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!expresionCorreo.test(email)) {

      return { success: false, error: 'Email no válido' };
    }
    const correoNormalizado = String(email || '').trim().toLowerCase();
    const rolNormalizado = role || 'pasajero';
    const esCorreoAdmin = correoNormalizado.endsWith('@admin.es');

    const mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

    if (rolNormalizado === 'admin' && !esCorreoAdmin) {
      // Evita que cualquiera se registre como admin usando un email no permitido.

      return { success: false, error: mensajeGenericoCredenciales };
    }

    if (rolNormalizado !== 'admin' && esCorreoAdmin) {
      // Evita que un correo de admin se registre como otro rol.
      
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

        localStorage.setItem('token', tokenAcceso.value);

        axios.defaults.headers.common['Authorization'] = `Bearer ${tokenAcceso.value}`;

        return { success: true };
      }

      return { success: false, error: 'Respuesta inesperada del servidor.' };
    } catch (errorCapturado) {

      return {
        success: false,
        error: errorCapturado.response?.data?.message || 'Error al intentar registrar usuario',
        errors: errorCapturado.response?.data?.errors || {}
      };
    }
  }

  return {
    user: usuario,
    token: tokenAcceso,
    login,
    register,
    getDashboardRoute
  };
}
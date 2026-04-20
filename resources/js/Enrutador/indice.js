import { createRouter, createWebHistory } from 'vue-router';

import { useAuthStore } from '../Almacenes/almacenAutenticacion';

/**
 * Enrutador principal de la SPA (Vue Router).
 *
 * - Define rutas públicas (inicio/login/registro) y privadas (paneles).
 * - Usa lazy-loading (`import()`) para reducir el tamaño del bundle inicial.
 * - Implementa un guard global (`beforeEach`) que:
 *   1) Verifica el estado de autenticación si el store aún no está inicializado.
 *   2) Bloquea rutas privadas si no hay sesión, redirigiendo a `/login`.
 *   3) Evita que usuarios autenticados vuelvan a `/login` o `/register`.
 */

// Carga diferida de páginas (code-splitting).
const Inicio = () => import('../Paginas/Inicio.vue');
const Login = () => import('../Paginas/Auth/Login.vue');
const Registro = () => import('../Paginas/Auth/Register.vue');
const PanelPasajero = () => import('../Paginas/Pasajero/Panel.vue');
const PanelConductor = () => import('../Paginas/Conductor/Panel.vue');
const PanelAdministrador = () => import('../Paginas/Administrador/Panel.vue');

// Tabla de rutas. `meta.requiresAuth` actúa como “flag” de protección.
const rutas = [
  {
    path: '/',
    name: 'inicio',
    component: Inicio,
  },
  {
    path: '/conductor/perfil',
    name: 'conductor-perfil',
    component: () => import('../Paginas/Conductor/Perfil.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
  },
  {
    path: '/register',
    name: 'register',
    component: Registro,
  },
  {
    path: '/dashboard',
    name: 'pasajero-dashboard',
    component: PanelPasajero,
    meta: { requiresAuth: true },
  },
  {
    path: '/conductor/dashboard',
    name: 'conductor-dashboard',
    component: PanelConductor,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: PanelAdministrador,
    meta: { requiresAuth: true },
  },
];

const enrutador = createRouter({
  history: createWebHistory(),
  routes: rutas,
});

// Guard global: se ejecuta antes de cada navegación.
enrutador.beforeEach(async (destino, origen) => {
  const autenticacion = useAuthStore();

  if (!autenticacion.inicializado) {
    // Asegura que el store conozca si hay token/sesión activa.
    await autenticacion.verificarAutenticacion();
  }

  if (destino.meta.requiresAuth && !autenticacion.estaAutenticado) {
    // Si la ruta requiere auth y el usuario no está autenticado, manda a login.
    
    return { path: '/login' };
  }

  if (autenticacion.estaAutenticado && (destino.path === '/login' || destino.path === '/register')) {
    // Si ya está autenticado, evita mostrar login/registro y redirige al panel.
    const rutaDashboard = autenticacion.obtenerRutaDashboard();

    return { path: rutaDashboard };
  }

  // Permite navegación.
  return true;
});

export default enrutador;
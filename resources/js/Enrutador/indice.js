// Router principal de la SPA (Vue Router)
// - Define rutas
// - Configura el history
// - Aplica un guard global para proteger vistas con autenticación
import { createRouter, createWebHistory } from 'vue-router';

// Store de autenticación (Pinia): usado por el guard para comprobar sesión y redirecciones
import { useAuthStore } from '../Almacenes/almacenAutenticacion';

// Importaciones lazy: reducen el tamaño inicial y cargan la vista bajo demanda
const Inicio = () => import('../Paginas/Inicio.vue');
const Login = () => import('../Paginas/Auth/Login.vue');
const Registro = () => import('../Paginas/Auth/Register.vue');
const PanelPasajero = () => import('../Paginas/Pasajero/Panel.vue');
const PanelConductor = () => import('../Paginas/Conductor/Panel.vue');
const PanelAdministrador = () => import('../Paginas/Administrador/Panel.vue');

// Definición de rutas
// Nota: `meta.requiresAuth` se usa en el guard para bloquear acceso a usuarios no autenticados.
const rutas = [
  {
    // Inicio
    path: '/',
    name: 'inicio',
    component: Inicio,
  },
  {
    // Perfil del conductor
    path: '/conductor/perfil',
    name: 'conductor-perfil',
    component: () => import('../Paginas/Conductor/Perfil.vue'),
    meta: { requiresAuth: true }
  },
  {
    // Inicio de sesión
    path: '/login',
    name: 'login',
    component: Login,
  },
  {
    // Registro
    path: '/register',
    name: 'register',
    component: Registro,
  },
  {
    // Dashboard pasajero (zona privada)
    path: '/dashboard',
    name: 'pasajero-dashboard',
    component: PanelPasajero,
    meta: { requiresAuth: true },
  },
  {
    // Dashboard conductor (zona privada)
    path: '/conductor/dashboard',
    name: 'conductor-dashboard',
    component: PanelConductor,
    meta: { requiresAuth: true },
  },
  {
    // Panel de administración (zona privada)
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: PanelAdministrador,
    meta: { requiresAuth: true },
  },
];

// Instancia del router
const enrutador = createRouter({
  history: createWebHistory(),
  routes: rutas,
});

// Guard global: se ejecuta en cada navegación
// - Inicializa/auth-check si aún no se ha hecho
// - Redirige a /login si la ruta requiere autenticación
// - Evita que usuarios logueados entren a /login o /register (los manda a su dashboard)
enrutador.beforeEach(async (destino, origen) => {
  const autenticacion = useAuthStore();

  if (!autenticacion.inicializado) {
    // Comprueba si hay sesión válida (por ejemplo token/cookie)
    await autenticacion.verificarAutenticacion();
  }

  if (destino.meta.requiresAuth && !autenticacion.estaAutenticado) {
    // Ruta privada sin sesión: ir a login
    return { path: '/login' };
  }

  if (autenticacion.estaAutenticado && (destino.path === '/login' || destino.path === '/register')) {
    // Usuario con sesión intentando acceder a login/registro: redirigir según rol
    const rutaDashboard = autenticacion.obtenerRutaDashboard();

    return { path: rutaDashboard };
  }

  // Permite la navegación
  return true;
});

export default enrutador;
// Router principal de la SPA (Vue Router)
// - Define rutas
// - Configura el history
// - Aplica un guard global para proteger vistas con autenticación
import { createRouter, createWebHistory } from 'vue-router';

// Importaciones lazy: reducen el tamaño inicial y cargan la vista bajo demanda
const Home = () => import('../Paginas/Inicio.vue');
const PanelPasajero = () => import('../Paginas/Pasajero/Panel.vue');

// Store de autenticación (Pinia): usado por el guard para comprobar sesión y redirecciones
import { useAuthStore } from '../Almacenes/almacenAutenticacion';

// Definición de rutas
// Nota: `meta.requiresAuth` se usa en el guard para bloquear acceso a usuarios no autenticados.
const routes = [
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
    component: Register,
  },
  {
    // Dashboard (zona privada)
    path: '/dashboard',
    name: 'pasajero-dashboard',
    component: PanelPasajero,
    meta: { requiresAuth: true },
    component: conductorDashboard,
    meta: { requiresAuth: true },
  },
  {
    // Panel de administración
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true },
  },
];

// Instancia del router
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Guard global: se ejecuta en cada navegación
// - Inicializa/auth-check si aún no se ha hecho
// - Redirige a /login si la ruta requiere autenticación
// - Evita que usuarios logueados entren a /login o /register (los manda a su dashboard)
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  if (!auth.initialized) {
    // Comprueba si hay sesión válida (por ejemplo token/cookie)
    await auth.checkAuth();
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    // Ruta privada sin sesión: ir a login
    return { path: '/login' };
  }

  if (auth.isAuthenticated && (to.path === '/login' || to.path === '/register')) {
    // Usuario con sesión intentando acceder a login/registro: redirigir según rol
    const dashboardRoute = auth.getDashboardRoute();

    return { path: dashboardRoute };
  }

  // Permite la navegación
  return true;
});

export default router;
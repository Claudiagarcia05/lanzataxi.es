<template>
  <div class="min-h-screen bg-neutral-soft dark:bg-gray-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-white focus:text-neutral-dark focus:px-4 focus:py-2 focus:rounded-lg">
      {{ t('nav.skipToContent') }}
    </a>
    <div v-if="menuMovilAbierto" class="fixed inset-0 z-30 bg-neutral-dark/30 md:hidden" @click="menuMovilAbierto = false" aria-hidden="true"/>

    <aside :class="[ 'fixed left-0 top-0 z-40 h-screen transition-all duration-300 bg-white dark:bg-gray-800 border-r border-neutral-volcanic dark:border-gray-700 shadow-lg', 'w-64 max-w-[85vw] md:max-w-none', barraLateralAbierta ? 'md:w-64' : 'md:w-20', menuMovilAbierto ? 'translate-x-0' : '-translate-x-full', 'md:translate-x-0',]">
      <div class="relative flex items-center p-4 border-b border-neutral-volcanic dark:border-gray-700 h-20">
        <div v-if="barraLateralAbierta" class="flex items-center space-x-2 flex-1 min-w-0">
          <img src="/images/logo_sin_fondo.png" alt="LanzaTaxi" class="h-10 w-auto object-contain">
          <span class="font-bold text-lanzarote-blue text-lg">LanzaTaxi</span>
        </div>
        <div v-else class="flex-1 min-w-0 flex justify-center">
          <img src="/images/logo_sin_fondo.png" alt="LanzaTaxi" class="h-10 w-10 object-contain">
        </div>

        <button class="ml-auto p-2 rounded-lg hover:bg-neutral-soft transition-colors md:hidden" @click="menuMovilAbierto = false" :aria-label="t('dashboard.pendingDrivers.close')" type="button">
          <span class="text-neutral-slate font-semibold text-lg leading-none">X</span>
        </button>
      </div>

      <div class="relative p-4 border-b border-neutral-volcanic dark:border-gray-700">
        <div class="flex items-center space-x-3 pr-12">
          <div v-if="barraLateralAbierta" class="overflow-hidden">
            <p class="font-semibold text-neutral-dark dark:text-gray-100 truncate">{{ authStore.usuario?.name }}</p>
            <p class="text-xs text-neutral-slate dark:text-gray-400">{{ t('dashboard.roles.passenger') }}</p>
          </div>
        </div>

        <button @click="alternarBarraLateral" class="absolute right-4 top-1/2 -translate-y-1/2 p-1.5 rounded-lg hover:bg-neutral-soft transition-colors" :aria-label="barraLateralAbierta ? t('dashboard.toggleMenu.collapse') : t('dashboard.toggleMenu.expand')" type="button">
          <svg class="w-5 h-5 text-neutral-slate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="barraLateralAbierta" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <nav class="p-4" aria-label="Navegacion lateral">
        <ul class="space-y-1">
          <li v-for="item in elementosMenu" :key="item.label">
            <button @click="navegarA(item.path)" :class="[ 'flex items-center space-x-3 p-3 rounded-lg w-full transition-colors', item.activo ? 'bg-lanzarote-blue/10 text-lanzarote-blue' : 'text-neutral-dark dark:text-gray-300 hover:bg-neutral-soft dark:hover:bg-gray-700' ]" type="button">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
              </svg>
              <span v-if="barraLateralAbierta" class="text-sm font-medium">{{ item.label }}</span>
            </button>
          </li>
        </ul>
      </nav>

      <div class="absolute bottom-0 w-full p-4 border-t border-neutral-volcanic dark:border-gray-700">
        <button @click="alternarModoOscuro" class="flex items-center space-x-3 p-3 rounded-lg text-neutral-dark dark:text-gray-300 hover:bg-neutral-soft dark:hover:bg-gray-700 w-full transition-colors mb-1" type="button" :aria-label="modoOscuro ? t('darkMode.disable') : t('darkMode.enable')">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="modoOscuro" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <span v-if="barraLateralAbierta" class="text-sm font-medium">{{ modoOscuro ? t('darkMode.disable') : t('darkMode.enable') }}</span>
        </button>
        <button @click="cerrarSesion" class="flex items-center space-x-3 p-3 rounded-lg text-neutral-dark dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 w-full transition-colors" type="button">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="barraLateralAbierta" class="text-sm font-medium">{{ t('dashboard.logout') }}</span>
        </button>
      </div>
    </aside>

    <div :class="['transition-all duration-300', 'ml-0', barraLateralAbierta ? 'md:ml-64' : 'md:ml-20']">
      <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-30">
        <div class="flex justify-between items-center px-4 py-4 md:px-6">
          <div>
            <h1 class="text-xl font-semibold text-neutral-dark dark:text-gray-100">{{ t('dashboard.panels.passenger') }}</h1>
            <p class="text-sm text-neutral-slate dark:text-gray-400">{{ fechaActualFormateada }}</p>
          </div>

          <button class="p-2 rounded-lg hover:bg-neutral-soft dark:hover:bg-gray-700 md:hidden" type="button" @click="menuMovilAbierto = true" :aria-label="barraLateralAbierta ? t('dashboard.toggleMenu.collapse') : t('dashboard.toggleMenu.expand')">
            <svg class="w-6 h-6 text-neutral-slate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

        </div>
      </header>

      <main id="main-content" class="p-4 md:p-6" tabindex="-1">
        <slot />
      </main>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router as inertiaRouter, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../Almacenes/almacenAutenticacion.js'
import { useViajeStore } from '../Almacenes/almacenViaje.js'
import { apiFetch } from '../Servicios/apiFetch'
import { useModoOscuro } from '../Composables/useModoOscuro.js'

/**
 * Disposición (layout) del panel de pasajero.
 *
 * Responsabilidades:
 * - Sidebar + header del rol pasajero.
 * - Inicializa autenticación y carga viajes.
 * - Sondea periódicamente notificaciones desde `/api/notifications`.
 * - Detiene sondeos/intervalos al desmontar.
 */

const authStore = useAuthStore()
const viajeStore = useViajeStore()
const page = usePage()
const { t, locale } = useI18n()
const { modoOscuro, inicializar, alternarModoOscuro } = useModoOscuro()

const barraLateralAbierta = ref(true)
const menuMovilAbierto = ref(false)

const alternarBarraLateral = () => {
  // Colapsa/expande sidebar en desktop.
  barraLateralAbierta.value = !barraLateralAbierta.value
}

const mostrarNotificaciones = ref(false)
const notificaciones = ref([])
const cargandoNotificaciones = ref(false)
const errorNotificaciones = ref(false)

const cargarNotificaciones = async () => {
  // Carga notificaciones para el usuario autenticado.
  // `errorNotificaciones` actúa como “fusible” para no insistir si el endpoint falla.
  if (errorNotificaciones.value) return
  
  cargandoNotificaciones.value = true
  try {
    const response = await apiFetch('/api/notifications')
    notificaciones.value = Array.isArray(response) ? response : []
  } catch (error) {
    console.error('❌ Error al cargar notificaciones:', error.status, error.message)
    errorNotificaciones.value = true
    notificaciones.value = []
  } finally {
    cargandoNotificaciones.value = false
  }
}

let intervaloNotificaciones = null

onMounted(() => {
  inicializar()
  // Inicialización del panel: auth -> viajes -> sondeo + notificaciones.
  if (!authStore.inicializado) {
    authStore.verificarAutenticacion().finally(() => {
      viajeStore.obtenerViajes()
      viajeStore.iniciarSondeo(5000)
    })
  } else {
    viajeStore.obtenerViajes()
    viajeStore.iniciarSondeo(5000)
  }
  cargarNotificaciones()
  
  intervaloNotificaciones = setInterval(() => {
    if (!errorNotificaciones.value) {
      cargarNotificaciones()
    }
  }, 30000)
})

onUnmounted(() => {
  // Limpieza: detener sondeos e intervalos.
  viajeStore.detenerSondeo()
  if (intervaloNotificaciones) {
    clearInterval(intervaloNotificaciones)
    intervaloNotificaciones = null
  }
})

const rutaActual = computed(() => {
  // Normaliza el path actual (sin query) para marcar el menú activo.
  const url = page.url || (typeof window !== 'undefined' ? window.location.pathname : '/')

  return String(url).split('?')[0]
})

const localeFecha = computed(() => (String(locale.value || 'es').startsWith('en') ? 'en-GB' : 'es-ES'))

const fechaActualFormateada = computed(() => {

  return new Date().toLocaleDateString(localeFecha.value, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const notificacionesNoLeidas = computed(() => notificaciones.value.filter(n => !n.read_at).length)

const avatarUsuario = computed(() => {

  return authStore.usuario?.avatar || null
})

const elementosMenu = computed(() => {
  // Dependemos de `locale` para recalcular labels al cambiar idioma.
  locale.value

  return [
  {
    label: t('dashboard.menu.newBooking'),
    icon: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    path: '/pasajero/home',
    activo: rutaActual.value === '/pasajero/home' || rutaActual.value === '/pasajero',
  },
  {
    label: t('dashboard.menu.myBookings'),
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    path: '/pasajero/reservas',
    activo: rutaActual.value.includes('/pasajero/reservas'),
  },
  {

    label: t('dashboard.menu.myProfile'),
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    path: '/pasajero/perfil',
    activo: rutaActual.value === '/pasajero/perfil',
  },
  ]
})

const navegarA = (path) => {
  inertiaRouter.visit(path)
}

const marcarComoLeida = async (id) => {
  // Persiste lectura en backend y actualiza estado local.
  try {
    await apiFetch(`/api/notifications/${id}/read`, { method: 'POST' })
    const notif = notificaciones.value.find(n => n.id === id)
    if (notif) notif.read_at = new Date().toISOString()
  } catch (error) {
    console.error('Error al marcar notificación:', error)
  }
}

const cerrarSesion = async () => {
  // Cierra sesión y vuelve a landing.
  await authStore.cerrarSesion()
  inertiaRouter.visit('/')
}
</script>
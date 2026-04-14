<template>
  <div class="min-h-screen bg-neutral-soft">
    <aside :class="[ 'fixed left-0 top-0 z-40 h-screen transition-all duration-300 bg-white border-r border-neutral-volcanic shadow-lg', barraLateralAbierta ? 'w-64' : 'w-20' ]">
      <div class="relative flex items-center p-4 border-b border-neutral-volcanic h-20">
        <div v-if="barraLateralAbierta" class="flex items-center space-x-2 flex-1 min-w-0">
          <img src="/images/logo_sin_fondo.png" alt="LanzaTaxi" class="h-10 w-auto object-contain">
          <span class="font-bold text-lanzarote-blue text-lg">LanzaTaxi</span>
        </div>
        <div v-else class="flex-1 min-w-0 flex justify-center">
          <img src="/images/logo_sin_fondo.png" alt="LanzaTaxi" class="h-10 w-10 object-contain">
        </div>
      </div>

      <div class="relative p-4 border-b border-neutral-volcanic">
        <div class="flex items-center space-x-3 pr-12">
          <div v-if="barraLateralAbierta" class="overflow-hidden">
            <p class="font-semibold text-neutral-dark truncate">{{ authStore.usuario?.name }}</p>
            <p class="text-xs text-neutral-slate">{{ t('dashboard.roles.passenger') }}</p>
          </div>
        </div>

        <button @click="alternarBarraLateral" class="absolute right-4 top-1/2 -translate-y-1/2 p-1.5 rounded-lg hover:bg-neutral-soft transition-colors" :aria-label="barraLateralAbierta ? t('dashboard.toggleMenu.collapse') : t('dashboard.toggleMenu.expand')">
          <svg class="w-5 h-5 text-neutral-slate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="barraLateralAbierta" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <nav class="p-4">
        <ul class="space-y-1">
          <li v-for="item in elementosMenu" :key="item.label">
            <button @click="navegarA(item.path)" :class="[ 'flex items-center space-x-3 p-3 rounded-lg w-full transition-colors', item.activo ? 'bg-lanzarote-blue/10 text-lanzarote-blue' : 'text-neutral-dark' ]">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
              </svg>
              <span v-if="barraLateralAbierta" class="text-sm font-medium">{{ item.label }}</span>
            </button>
          </li>
        </ul>
      </nav>

      <div class="absolute bottom-0 w-full p-4 border-t border-neutral-volcanic">
        <button @click="cerrarSesion" class="flex items-center space-x-3 p-3 rounded-lg text-neutral-dark hover:bg-red-50 hover:text-red-600 w-full transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="barraLateralAbierta" class="text-sm font-medium">{{ t('dashboard.logout') }}</span>
        </button>
      </div>
    </aside>

    <div :class="['transition-all duration-300', barraLateralAbierta ? 'ml-64' : 'ml-20']">
      <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="flex justify-between items-center px-6 py-4">
          <div>
            <h1 class="text-xl font-semibold text-neutral-dark">{{ t('dashboard.panels.passenger') }}</h1>
            <p class="text-sm text-neutral-slate">{{ fechaActualFormateada }}</p>
          </div>

          <!-- Eliminada campana y notificaciones -->
        </div>
      </header>

      <main class="p-6">
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
import axios from 'axios'

const authStore = useAuthStore()
const viajeStore = useViajeStore()
const page = usePage()
const { t, locale } = useI18n()

const barraLateralAbierta = ref(true)

const alternarBarraLateral = () => {
  barraLateralAbierta.value = !barraLateralAbierta.value
}

// Sidebar siempre abierto, sin estado de despliegue
const mostrarNotificaciones = ref(false)
const notificaciones = ref([])
const cargandoNotificaciones = ref(false)
const errorNotificaciones = ref(false)

const cargarNotificaciones = async () => {
  if (errorNotificaciones.value) return // Si hubo error, no intentar de nuevo
  
  cargandoNotificaciones.value = true
  try {
    const response = await axios.get('/api/notifications')
    notificaciones.value = response.data || []
  } catch (error) {
    console.error('❌ Error al cargar notificaciones:', error.response?.status, error.message)
    errorNotificaciones.value = true
    // Mostrar notificaciones vacías si hay error
    notificaciones.value = []
  } finally {
    cargandoNotificaciones.value = false
  }
}

let intervaloNotificaciones = null

onMounted(() => {
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
  
  // Actualizar notificaciones cada 30 segundos (solo si no hay error)
  intervaloNotificaciones = setInterval(() => {
    if (!errorNotificaciones.value) {
      cargarNotificaciones()
    }
  }, 30000)
})

onUnmounted(() => {
  viajeStore.detenerSondeo()
  if (intervaloNotificaciones) {
    clearInterval(intervaloNotificaciones)
    intervaloNotificaciones = null
  }
})

const rutaActual = computed(() => {
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
  try {
    await axios.post(`/api/notifications/${id}/read`)
    const notif = notificaciones.value.find(n => n.id === id)
    if (notif) notif.read_at = new Date().toISOString()
  } catch (error) {
    console.error('Error al marcar notificación:', error)
  }
}

const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  inertiaRouter.visit('/')
}
</script>
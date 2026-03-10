<template>
  <div class="min-h-screen bg-neutral-soft">
    <aside :class="[ 'fixed left-0 top-0 z-40 h-screen transition-all duration-300 bg-white border-r border-neutral-volcanic shadow-lg', isSidebarOpen ? 'w-64' : 'w-20' ]">
      <div class="flex items-center justify-between p-4 border-b border-neutral-volcanic h-20">
        <div v-if="isSidebarOpen" class="flex items-center space-x-2">
          <img src="/images/logo.png" alt="LanzaTaxi" class="h-10 w-auto">
          <span class="font-bold text-lanzarote-blue text-lg">LanzaTaxi</span>
        </div>
        <div v-else class="w-full flex justify-center">
          <img src="/images/logo.png" alt="LanzaTaxi" class="h-10 w-auto">
        </div>
        <button @click="toggleSidebar" class="p-1.5 rounded-lg hover:bg-neutral-soft transition-colors">
          <svg class="w-5 h-5 text-neutral-slate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="isSidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <div class="p-4 border-b border-neutral-volcanic">
        <div class="flex items-center">
          <div v-if="isSidebarOpen" class="overflow-hidden">
            <p class="font-semibold text-neutral-dark truncate">{{ authStore.usuario?.name }}</p>
            <p class="text-xs text-neutral-slate">{{ getUserRoleText() }}</p>
            <p v-if="authStore.isconductor" class="text-xs mt-1">
            </p>
          </div>
        </div>
      </div>

      <nav class="p-4">
        <ul class="space-y-1">
          <li v-for="item in elementosMenu" :key="item.label">
            <button
              @click="navigateTo(item.path)"
              :class="[ 'flex items-center space-x-3 p-3 rounded-lg w-full transition-colors', item.activo ? 'bg-lanzarote-blue/10 text-lanzarote-blue' : 'text-neutral-dark hover:bg-neutral-soft' ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
              </svg>
              <span v-if="isSidebarOpen" class="text-sm font-medium">{{ item.label }}</span>
            </button>
          </li>
        </ul>
      </nav>

      <div v-if="authStore.isconductor && isSidebarOpen" class="px-4 mt-4">
        <button
          :class="[ 'w-full py-2 px-4 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2', ]"
        >
          <span>🟢</span>
        </button>
      </div>

      <div class="absolute bottom-0 w-full p-4 border-t border-neutral-volcanic">
        <button @click="logout" class="flex items-center space-x-3 p-3 rounded-lg text-neutral-dark hover:bg-red-50 hover:text-red-600 w-full transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="isSidebarOpen" class="text-sm font-medium">Cerrar sesión</span>
        </button>
      </div>
    </aside>

    <div :class="['transition-all duration-300', isSidebarOpen ? 'ml-64' : 'ml-20']">
      <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="flex justify-between items-center px-6 py-4">
          <div>
            <h1 class="text-xl font-semibold text-neutral-dark">{{ getDashboardTitle() }}</h1>
            <p class="text-sm text-neutral-slate">{{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
          </div>

          <div v-if="!authStore.isAdmin" class="flex items-center space-x-4">
            <div class="relative">
              <button @click="showNotifications = !showNotifications" class="p-2 rounded-lg hover:bg-neutral-soft relative">
                <svg class="w-5 h-5 text-neutral-slate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span v-if="unreadNotifications > 0" class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                  {{ unreadNotifications }}
                </span>
              </button>

              <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-neutral-volcanic">
                <div class="p-3 border-b border-neutral-volcanic">
                  <h3 class="font-semibold text-neutral-dark">Notificaciones</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                  <div v-for="notif in notificaciones" :key="notif.id"
                       @click="markAsRead(notif.id)"
                       :class="['p-3 border-b border-neutral-volcanic last:border-0 cursor-pointer hover:bg-neutral-soft', !notif.read && 'bg-lanzarote-blue/5']">
                    <p class="text-sm text-neutral-dark">{{ notif.text }}</p>
                    <p class="text-xs text-neutral-slate mt-1">{{ notif.time }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-3">
              <div class="text-right hidden md:block">
                <p class="text-sm font-medium text-neutral-dark">{{ authStore.usuario?.name }}</p>
                <p class="text-xs text-neutral-slate">{{ getUserRoleText() }}</p>
              </div>
              <div class="w-10 h-10 rounded-full bg-lanzarote-blue text-white flex items-center justify-center font-bold">
                {{ authStore.usuario?.name?.charAt(0) }}
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>

  <div v-if="authStore.isAdmin && adminStore.modalPendientesAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg border border-neutral-volcanic">
      <div class="p-5 border-b border-neutral-volcanic flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-neutral-dark">Solicitud de taxista</h3>
          <p class="text-sm text-neutral-slate">Revisa y decide: aprobar o rechazar.</p>
        </div>
        <button @click="adminStore.cerrarModalPendientes()" class="p-2 rounded-lg hover:bg-neutral-soft">
          <span class="text-neutral-slate">Cerrar</span>
        </button>
      </div>

      <div class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">
        <div v-for="solicitud in adminStore.pendientesNuevos" :key="solicitud.id" class="border border-neutral-volcanic rounded-lg p-4">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <p class="font-medium text-neutral-dark">{{ solicitud.name }}</p>
              <p class="text-sm text-neutral-slate">{{ solicitud.email }} · {{ solicitud.phone }}</p>
              <p class="text-xs text-neutral-slate mt-1">Licencia: {{ solicitud.license_number || '—' }} · Solicitó: {{ (solicitud.created_at || '').split('T')[0] }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="adminStore.aprobarConductor(solicitud.id)" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
                Aprobar
              </button>
              <button @click="adminStore.rechazarConductor(solicitud.id)" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600">
                Rechazar
              </button>
            </div>
          </div>
        </div>

        <div v-if="adminStore.pendientesNuevos.length === 0" class="text-sm text-neutral-slate">
          No hay nuevas solicitudes.
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../Almacenes/almacenAutenticacion.js'
import { useTripStore } from '../Almacenes/almacenViaje.js'
import { useConductorStore } from '../Almacenes/almacenConductor.js'
import { useAdminStore } from '../Almacenes/almacenAdministrador.js'
import { router as inertiaRouter, usePage } from '@inertiajs/vue3'

const authStore = useAuthStore()
const viajeStore = useTripStore()
const conductorStore = useConductorStore()
const adminStore = useAdminStore()
const page = usePage()

let pendingPollIntervalId = null

const isSidebarOpen = ref(true)
const showNotifications = ref(false)
const notificaciones = ref([
  { id: 1, text: 'Nueva solicitud de viaje', time: 'hace 2 min', read: false },
  { id: 2, text: 'Viaje completado con éxito', time: 'hace 15 min', read: false },
  { id: 3, text: 'Pago recibido: 18.50€', time: 'hace 1 hora', read: true }
])

onMounted(() => {
  ;(async () => {
    if (!authStore.initialized || !authStore.usuario) {
      await authStore.checkAuth()
    }

    if (authStore.ispasajero) {
      viajeStore.fetchTrips()
    } else if (authStore.isconductor) {
      viajeStore.fetchTrips()
      conductorStore.obtenerPerfilConductor()
    } else if (authStore.isAdmin) {
      adminStore.fetchAllData()

      pendingPollIntervalId = setInterval(() => {
        adminStore.obtenerConductoresPendientes({ openModalOnNew: true })
      }, 15000)
    }
  })()
})

onUnmounted(() => {
  if (pendingPollIntervalId) clearInterval(pendingPollIntervalId)
})

const logout = async () => {
  // Eliminado control de estado en línea para conductor (excepto Mi Perfil)
  authStore.logout()
  inertiaRouter.visit('/')
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const rutaActual = computed(() => {
  const url = page.url || (typeof window !== 'undefined' ? window.location.pathname : '/')
  return String(url).split('?')[0]
})

const getUserRoleText = () => {
  switch(authStore.usuario?.role) {
    case 'pasajero': return 'Pasajero'
    case 'conductor': return 'Taxista'
    case 'admin': return 'Administrador'
    default: return ''
  }
}

const getDashboardTitle = () => {
  if (authStore.isAdmin) return 'Panel Administrador'
  if (authStore.isconductor) return 'Panel Taxista'
  if (authStore.ispasajero) return 'Panel Pasajero'

  if (rutaActual.value.includes('/conductor')) return 'Panel Taxista'
  if (rutaActual.value.includes('/admin') || rutaActual.value.includes('/administradir')) return 'Panel Administrador'

  return 'Panel Pasajero'
}

const unreadNotifications = computed(() => {
  
  return notificaciones.value.filter(n => !n.read).length
})

const markAsRead = (id) => {
  const notif = notificaciones.value.find(n => n.id === id)
  if (notif) notif.read = true
}

const navigateTo = (path) => {
  inertiaRouter.visit(path)
  if (!isSidebarOpen.value) toggleSidebar()
}

const elementosMenu = computed(() => {
  const inicio = {
    label: 'Inicio',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    path: authStore.ispasajero ? '/dashboard' : authStore.isconductor ? '/conductor/dashboard' : '/admin/dashboard',
    activo: rutaActual.value === (authStore.ispasajero ? '/dashboard' : authStore.isconductor ? '/conductor/dashboard' : '/admin/dashboard') || rutaActual.value === '/administradir/home'
  }

  const miPerfil = {
    label: 'Mi Perfil',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    path: '/perfil',
    activo: rutaActual.value === '/perfil'
  }

  const misViajes = {
    label: 'Mis viajes',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    path: authStore.ispasajero ? '/dashboard/viajes' : '/conductor/viajes',
    activo: rutaActual.value.includes('viajes')
  }

  const ganancias = {
    label: 'Ganancias',
    icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    path: '/conductor/ganancias',
    activo: rutaActual.value.includes('/conductor/ganancias')
  }

  const taxistas = {
    label: 'Taxistas',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    path: '/admin/taxistas',
    activo: rutaActual.value.includes('/admin/taxistas')
  }

  const clientes = {
    label: 'Clientes',
    icon: 'M16 11c1.657 0 3-1.343 3-3S17.657 5 16 5s-3 1.343-3 3 1.343 3 3 3zM8 11c1.657 0 3-1.343 3-3S9.657 5 8 5 5 6.343 5 8s1.343 3 3 3zm0 2c-2.67 0-8 1.34-8 4v2h10v-2c0-1.29.84-2.4 2.1-3.25C11.2 13.29 9.56 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45v2h7v-2c0-2.66-5.33-4-8-4z',
    path: '/admin/clientes',
    activo: rutaActual.value.includes('/admin/clientes')
  }

  if (authStore.isAdmin) return [inicio, taxistas, clientes, miPerfil]
  if (authStore.isconductor) return [inicio, misViajes, ganancias, miPerfil]
  if (authStore.ispasajero) return [inicio, misViajes, miPerfil]

  return [inicio, miPerfil]
})
</script>
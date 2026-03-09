<template>
  <DisposicionTablero>
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-neutral-dark">Panel de AdministraciÃƒÂ³n Ã°Å¸â€˜â€˜</h2>
      <p class="text-neutral-slate">Bienvenido, {{ authStore.usuario?.name }}. Aquí puedes gestionar toda la plataforma.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="stat in estadisticas" :key="stat.label"
           class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
          <span class="text-2xl">{{ stat.icon }}</span>
          <span class="text-xs text-green-600">{{ stat.change }}</span>
        </div>
        <p class="text-2xl font-bold text-neutral-dark">{{ stat.value }}</p>
        <p class="text-sm text-neutral-slate">{{ stat.label }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-neutral-dark mb-4">Actividad semanal</h3>
        <div class="flex items-end h-40 space-x-2">
          <div v-for="(valor, index) in actividadDatos" :key="index" class="flex-1 flex flex-col items-center">
            <div class="w-full bg-lanzarote-blue/20 rounded-t-lg" :style="{ height: valor + 'px' }">
              <div class="w-full bg-lanzarote-blue rounded-t-lg" :style="{ height: (valor * 0.7) + 'px' }"></div>
            </div>
            <span class="text-xs mt-2 text-neutral-slate">{{ actividadSemanas[index] }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-neutral-dark mb-4">Acciones rápidas</h3>
        <div class="space-y-3">
          <button class="w-full p-3 bg-neutral-soft rounded-lg text-left hover:bg-lanzarote-blue/10 transition-colors">
            <span class="font-medium">Ã¢Å¾â€¢ Nuevo administrador</span>
          </button>
          <button class="w-full p-3 bg-neutral-soft rounded-lg text-left hover:bg-lanzarote-blue/10 transition-colors">
            <span class="font-medium">Ã°Å¸â€œÅ  Generar reporte</span>
          </button>
          <button class="w-full p-3 bg-neutral-soft rounded-lg text-left hover:bg-lanzarote-blue/10 transition-colors">
            <span class="font-medium">Ã¢Å¡â„¢Ã¯Â¸Â ConfiguraciÃƒÂ³n general</span>
          </button>
          <button class="w-full p-3 bg-neutral-soft rounded-lg text-left hover:bg-lanzarote-blue/10 transition-colors">
            <span class="font-medium">Ã°Å¸â€œÂ§ Enviar newsletter</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="solicitudesPendientes.length > 0" class="mb-8">
      <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
        <h3 class="font-semibold text-yellow-800 mb-4 flex items-center">
          <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
          Solicitudes de taxistas pendientes ({{ solicitudesPendientes.length }})
        </h3>
        <div class="space-y-3">
          <div v-for="solicitud in solicitudesPendientes" :key="solicitud.id"
               class="flex items-center justify-between bg-white p-4 rounded-lg">
            <div>
              <p class="font-medium text-neutral-dark">{{ solicitud.name }}</p>
              <p class="text-sm text-neutral-slate">{{ solicitud.email }} Ã‚Â· {{ solicitud.phone }}</p>
              <p class="text-xs text-neutral-slate mt-1">Licencia: {{ solicitud.license }} • Solicitó: {{ solicitud.appliedAt }}</p>
            </div>
            <div class="flex space-x-2">
              <button @click="approveconductor(solicitud.id)"
                      class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
                Aprobar
              </button>
              <button @click="rejectconductor(solicitud.id)"
                      class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600">
                Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-sm">
        <div class="p-6 border-b border-neutral-volcanic flex justify-between items-center">
          <h3 class="font-semibold text-neutral-dark">Usuarios recientes</h3>
          <button class="text-sm text-lanzarote-blue hover:underline">Ver todos</button>
        </div>
        <div class="divide-y divide-neutral-volcanic">
          <div v-for="usuario in adminStore.usuarios.slice(0, 4)" :key="usuario.id"
               class="p-4 hover:bg-neutral-soft">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-medium text-neutral-dark">{{ usuario.name }}</p>
                <p class="text-sm text-neutral-slate">{{ usuario.email }}</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="text-xs bg-neutral-100 px-2 py-0.5 rounded-full">{{ usuario.role === 'pasajero' ? 'Pasajero' : usuario.role === 'conductor' ? 'Taxista' : 'Admin' }}</span>
                  <span class="text-xs text-neutral-slate">Unido: {{ usuario.fechaRegistro }}</span>
                </div>
              </div>
              <span :class="[
                'text-xs px-2 py-1 rounded-full',
                usuario.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-neutral-100 text-neutral-slate'
              ]">
                {{ usuario.estado === 'activo' ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm">
        <div class="p-6 border-b border-neutral-volcanic flex justify-between items-center">
          <h3 class="font-semibold text-neutral-dark">Viajes recientes</h3>
          <button class="text-sm text-lanzarote-blue hover:underline">Ver todos</button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-soft">
              <tr>
                <th class="text-left p-4 text-xs font-medium text-neutral-slate">Pasajero</th>
                <th class="text-left p-4 text-xs font-medium text-neutral-slate">Taxista</th>
                <th class="text-left p-4 text-xs font-medium text-neutral-slate">Ruta</th>
                <th class="text-left p-4 text-xs font-medium text-neutral-slate">Precio</th>
                <th class="text-left p-4 text-xs font-medium text-neutral-slate">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="viaje in viajesRecientes" :key="viaje.id" class="border-b border-neutral-volcanic hover:bg-neutral-soft">
                <td class="p-4">
                  <p class="font-medium text-sm">{{ viaje.pasajeroName }}</p>
                </td>
                <td class="p-4 text-sm">{{ viaje.conductorName || '–' }}</td>
                <td class="p-4">
                  <p class="text-xs text-neutral-slate">{{ viaje.pickup }} Ã¢â€ â€™ {{ viaje.dropoff }}</p>
                </td>
                <td class="p-4 font-medium">{{ viaje.price }} Ã¢â€šÂ¬</td>
                <td class="p-4">
                  <span :class="['px-2 py-1 rounded-full text-xs', getStatusClass(viaje.estado)]">
                    {{ viaje.estado }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm">
      <div class="p-6 border-b border-neutral-volcanic flex justify-between items-center">
        <h3 class="font-semibold text-neutral-dark">Taxis activos</h3>
        <button class="text-sm text-lanzarote-blue hover:underline">Gestionar flota</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
        <div v-for="taxi in adminStore.taxis" :key="taxi.id"
             class="border border-neutral-volcanic rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-2">
            <span class="font-medium">{{ taxi.plate }}</span>
            <span :class="[
              'text-xs px-2 py-1 rounded-full',
              taxi.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
            ]">
              {{ taxi.estado === 'activo' ? 'Activo' : 'Mantenimiento' }}
            </span>
          </div>
          <p class="text-sm text-neutral-slate">{{ taxi.model }} ({{ taxi.year }})</p>
          <p class="text-sm text-neutral-slate">Conductor: {{ taxi.conductor || 'Sin asignar' }}</p>
        </div>
      </div>
    </div>
  </DisposicionTablero>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import DisposicionTablero from '../../Disposiciones/DisposicionTablero.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'
import { useTripStore } from '../../Almacenes/almacenViaje.js'

const authStore = useAuthStore()
const adminStore = useAdminStore()
const viajeStore = useTripStore()

onMounted(() => {
  adminStore.fetchAllData()
  viajeStore.fetchTrips()
})

const estadisticas = computed(() => [
  { label: 'Usuarios totales', value: adminStore.estadisticas.totalUsers, icon: 'Ã°Å¸â€˜Â¥', change: '+124 este mes' },
  { label: 'Taxis activos', value: adminStore.activeTaxis, icon: 'Ã°Å¸Å¡â€¢', change: `de ${adminStore.estadisticas.totalTaxis} totales` },
  { label: 'Viajes hoy', value: adminStore.estadisticas.viajesHoy, icon: 'Ã°Å¸â€œÅ ', change: '+12% vs ayer' },
  { label: 'Ingresos hoy', value: adminStore.revenueToday, icon: 'Ã°Å¸â€™Â°', change: `media ${(adminStore.estadisticas.ingresosHoy / adminStore.estadisticas.viajesHoy).toFixed(2)} Ã¢â€šÂ¬/viaje` }
])

const solicitudesPendientes = computed(() => adminStore.pendingconductors)

const viajesRecientes = computed(() => {
  return viajeStore.viajes
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .slice(0, 5)
})

const actividadSemanas = ref(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'])
const actividadDatos = ref([65, 72, 80, 78, 92, 85, 70])

const approveconductor = async (conductorId) => {
  if (confirm('¿Aprobar esta solicitud de taxista?')) {
    await adminStore.approveconductor(conductorId)
  }
}

const rejectconductor = async (conductorId) => {
  if (confirm('¿Rechazar esta solicitud?')) {
    await adminStore.rejectconductor(conductorId)
  }
}

const getStatusClass = (estado) => {
  const classes = {
    'completed': 'bg-green-100 text-green-800',
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'accepted': 'bg-lanzarote-blue/10 text-lanzarote-blue',
    'in_progress': 'bg-lanzarote-yellow/20 text-lanzarote-blue',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return classes[estado] || 'bg-neutral-100 text-neutral-slate'
}
</script>

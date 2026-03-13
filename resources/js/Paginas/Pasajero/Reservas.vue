<template>
  <DisposicionPasajero>
    <!-- Página del pasajero: historial de reservas y acceso a seguimiento en tiempo real -->
    <div class="max-w-7xl mx-auto">
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <!-- Título de la sección -->
        <h1 class="text-3xl font-bold text-neutral-dark">Mis Reservas</h1>
        <p class="text-neutral-slate mt-1">Historial y seguimiento de tus viajes</p>
      </div>

      <!-- Tarjetas resumen (totales) -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-neutral-dark">{{ viajeStats.all }}</p>
          <p class="text-sm text-neutral-slate">Total reservas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-green-600">{{ viajeStats.completed }}</p>
          <p class="text-sm text-neutral-slate">Completadas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-lanzarote-blue">{{ totalGastado.toFixed(2) }}€</p>
          <p class="text-sm text-neutral-slate">Total gastado</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <!-- Mensajes de estado (errores e información) -->
        <div v-if="errorMsg" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-red-500">{{ errorMsg }}</p>
        </div>
        <div v-if="infoMsg" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-green-500">{{ infoMsg }}</p>
        </div>
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-semibold text-neutral-dark">Historial de Viajes</h2>
          <span class="text-sm text-neutral-slate">{{ filteredviajes.length }} reservas</span>
        </div>

        <div class="space-y-4">
          <!-- Listado de viajes (filtrados y ordenados por fecha) -->
          <div v-for="viaje in filteredviajes" :key="viaje.id" class="border border-neutral-volcanic rounded-xl p-5 hover:shadow-md transition-shadow">
            <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-neutral-dark">{{ viaje.dropoff_address }}</h3>
                <p class="text-sm text-neutral-slate">→ {{ viaje.pickup_address }}</p>
              </div>
              <div class="flex items-center gap-3 mt-2 md:mt-0">
                <span class="text-sm text-neutral-slate">{{ formatDate(viaje.created_at) }}</span>
                <span v-if="viaje.conductor?.usuario?.name" class="text-sm font-medium text-neutral-dark">{{ viaje.conductor.usuario.name }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <!-- Datos principales del viaje -->
              <div>
                <p class="text-xs text-neutral-slate">Precio</p>
                <p class="text-lg font-bold text-lanzarote-blue">{{ viaje.price?.toFixed(2) }}€</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">Distancia</p>
                <p class="font-medium text-neutral-dark">{{ viaje.distance?.toFixed(1) }} km</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">Pasajeros</p>
                <p class="font-medium text-neutral-dark">{{ viaje.pasajeros || 1 }}</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">Estado</p>
                <span :class="['px-2 py-1 rounded-full text-xs font-medium inline-block', getStatusBadge(viaje.estado).class]">
                  {{ getStatusBadge(viaje.estado).label }}
                </span>
              </div>
            </div>

            <div v-if="['completed', 'cancelled'].includes(viaje.estado)" class="border-t border-neutral-volcanic pt-4 mt-2">
              <!-- Viaje finalizado/cancelado: se muestra estado del pago -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-neutral-slate">Pago:</span>
                  <span v-if="viaje.estado === 'cancelled'" class="text-green-600 font-medium">Pagado</span>
                  <span v-else-if="viaje.pago && viaje.pago.status === 'paid'" class="text-green-600 font-medium">Pagado</span>
                  <span v-else class="text-yellow-600 font-medium">Pendiente de pago</span>
                </div>
              </div>

              <div v-if="viaje.valoracion" class="flex items-center gap-2 mt-2">
                </div>
            </div>

            <div v-else-if="viaje.estado === 'pendiente'" class="border-t border-neutral-volcanic pt-4 mt-2">
              <!-- Viaje pendiente: permite cancelar la reserva -->
              <div class="flex justify-end">
                <button @click="cancelTrip(viaje.id)" class="text-sm text-red-600 hover:text-red-800">
                  Cancelar reserva
                </button>
              </div>
            </div>

            <div v-else-if="['accepted', 'in_progress'].includes(viaje.estado)" class="border-t border-neutral-volcanic pt-4 mt-2">
              <!-- Viaje aceptado/en curso: acceso a seguimiento en tiempo real -->
              <div class="flex justify-end">
                <button @click="irASeguimiento(viaje.id)" class="text-sm text-lanzarote-blue hover:text-lanzarote-yellow">
                  Ver seguimiento en tiempo real
                </button>
              </div>
            </div>
          </div>

          <div v-if="filteredviajes.length === 0" class="text-center py-12">
            <p class="text-neutral-slate">No hay viajes que mostrar</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de valoración (actualmente sin contenido visible en este archivo) -->
    <div v-if="showRatingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      </div>

    <!-- Modal de pago reutilizable -->
    <ModalPago :show="showpagoModal" :viaje="pagoviaje" @close="showpagoModal = false" @success="handlepagoSuccess"/>
  </DisposicionPasajero>
    <!-- Confirmación de cancelación (evita confirm/alert nativos) -->
    <div v-if="showCancelConfirm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">¿Cancelar reserva?</h3>
        <p class="text-neutral-slate mb-6">¿Estás seguro de que deseas cancelar este viaje? Se cobrará el importe completo y, si no hay saldo suficiente en tu cartera, se generará una deuda.</p>
        <div class="flex space-x-3">
          <button @click="confirmCancelTrip" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Sí, cancelar</button>
          <button @click="cancelCancelTrip" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">No, volver</button>
        </div>
      </div>
    </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
const errorMsg = ref('')
const infoMsg = ref('')
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import ModalPago from '../../Componentes/ModalPago.vue'
import { useTripStore } from '../../Almacenes/almacenViaje.js'
import { useWalletStore } from '../../Almacenes/almacenCartera.js'

const viajeStore = useTripStore()
const walletStore = useWalletStore()

const filtroSeleccionado = ref('all')


const showpagoModal = ref(false)
const pagoviaje = ref(null)

const totalGastado = computed(() => {
  // Total gastado aproximado: suma viajes pagados menos deuda liquidada asociada
  let total = 0;
  let deudaPagada = 0;
  viajeStore.viajesPasajero.forEach(t => {
    if (t.estado === 'completed' || (t.estado === 'cancelled' && (!t.debt || t.debt === 0))) {
      total += t.price || 0;
      if (t.debt_paid) deudaPagada += t.debt_paid;
    }
  });

  return total - deudaPagada;
})

const filteredviajes = computed(() => {
  // Aplica el filtro seleccionado y ordena por fecha (más reciente primero)
  let viajes = [...viajeStore.viajesPasajero]

  if (filtroSeleccionado.value !== 'all') {
    viajes = viajes.filter(t => t.estado === filtroSeleccionado.value)
  }

  return viajes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const viajeStats = computed(() => {
  // Estadísticas rápidas para la cabecera
  const all = viajeStore.viajesPasajero.length
  const completed = viajeStore.viajesPasajero.filter(t => t.estado === 'completed').length
  const pendiente = viajeStore.viajesPasajero.filter(t => t.estado === 'pendiente').length
  const cancelled = viajeStore.viajesPasajero.filter(t => t.estado === 'cancelled').length
  const activo = viajeStore.viajesPasajero.filter(t => ['accepted', 'in_progress'].includes(t.estado)).length

  return { all, completed, pendiente, cancelled, activo }
})

const getStatusBadge = (estado) => {
  // Traduce estado a etiqueta + clases de estilo
  const badges = {
    'pendiente': { class: 'bg-yellow-100 text-yellow-800', label: 'Pendiente' },
    'accepted': { class: 'bg-blue-100 text-blue-800', label: 'Aceptado' },
    'in_progress': { class: 'bg-green-100 text-green-800', label: 'En curso' },
    'completed': { class: 'bg-gray-100 text-gray-800', label: 'Finalizado' },
    'cancelled': { class: 'bg-red-100 text-red-800', label: 'Cancelado' }
  }

  return badges[estado] || badges.pendiente
}

const cancelTrip = async (viajeId) => {
  // Abre el modal de confirmación; no cancela directamente
  errorMsg.value = ''
  infoMsg.value = ''
  showCancelConfirm.value = viajeId
}
const showCancelConfirm = ref(null)

const confirmCancelTrip = async () => {
  // Confirma la cancelación y refresca cartera/deuda
  const viajeId = showCancelConfirm.value
  await viajeStore.cancelTrip(viajeId)
  await walletStore.fetchBalance()
  await walletStore.fetchDebtSummary()
  infoMsg.value = 'Reserva cancelada correctamente.'
  setTimeout(() => { infoMsg.value = '' }, 4000)
  showCancelConfirm.value = null
}

const cancelCancelTrip = () => {
  showCancelConfirm.value = null
}


const openpagoModal = (viaje) => {
  pagoviaje.value = viaje
  showpagoModal.value = true
}

const handlepagoSuccess = async (pagoData) => {
  console.log('Pago exitoso:', pagoData)
  await viajeStore.fetchTrips()
  infoMsg.value = 'Pago procesado correctamente'
  setTimeout(() => { infoMsg.value = '' }, 4000)
}


const formatDate = (dateString) => {
  const date = new Date(dateString)
  
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).replace(',', '')
}

const irASeguimiento = (viajeId) => {
  // Navegación al detalle de seguimiento
  window.location.href = `/pasajero/seguimiento/${viajeId}`
}

onMounted(() => {
  viajeStore.fetchTrips()
  walletStore.fetchBalance()
  walletStore.fetchDebtSummary()
})
</script>
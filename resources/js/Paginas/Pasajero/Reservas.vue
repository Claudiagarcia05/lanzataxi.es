<template>
  <DisposicionPasajero>
    <!-- Página del pasajero: historial de reservas y acceso a seguimiento en tiempo real -->
    <div class="max-w-7xl mx-auto">
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <!-- Título de la sección -->
        <h1 class="text-3xl font-bold mb-2">Mis Reservas</h1>
        <p class="text-neutral-slate mt-1">Historial y seguimiento de tus viajes</p>
      </div>

      <!-- Tarjetas resumen (totales) -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-neutral-dark">{{ estadisticasViajes.total }}</p>
          <p class="text-sm text-neutral-slate">Total reservas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-green-600">{{ estadisticasViajes.completados }}</p>
          <p class="text-sm text-neutral-slate">Completadas</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-lanzarote-blue">{{ totalGastado.toFixed(2) }}€</p>
          <p class="text-sm text-neutral-slate">Total gastado</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <!-- Mensajes de estado (errores e información) -->
        <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
        </div>
        <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
        </div>
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-semibold text-neutral-dark">Historial de Viajes</h2>
          <span class="text-sm text-neutral-slate">{{ viajesFiltrados.length }} reservas</span>
        </div>

        <div class="space-y-4">
          <!-- Listado de viajes (filtrados y ordenados por fecha) -->
          <div v-for="viaje in viajesFiltrados" :key="viaje.id" class="border border-neutral-volcanic rounded-xl p-5 hover:shadow-md transition-shadow">
            <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-neutral-dark">{{ viaje.dropoff_address }}</h3>
                <p class="text-sm text-neutral-slate">→ {{ viaje.pickup_address }}</p>
              </div>
              <div class="flex items-center gap-3 mt-2 md:mt-0">
                <span class="text-sm text-neutral-slate">{{ formatearFecha(viaje.created_at) }}</span>
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
                <span :class="['px-2 py-1 rounded-full text-xs font-medium inline-block', obtenerDistintivoEstado(viaje.estado).class]">
                  {{ obtenerDistintivoEstado(viaje.estado).label }}
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
                <button @click="abrirConfirmacionCancelacion(viaje.id)" class="text-sm text-red-600 hover:text-red-800">
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

          <div v-if="viajesFiltrados.length === 0" class="text-center py-12">
            <p class="text-neutral-slate">No hay viajes que mostrar</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de valoración (actualmente sin contenido visible en este archivo) -->
    <div v-if="mostrarModalValoracion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      </div>

    <!-- Modal de pago reutilizable -->
    <ModalPago :show="mostrarModalPago" :viaje="viajePago" @close="mostrarModalPago = false" @success="manejarPagoExitoso"/>
  </DisposicionPasajero>
    <!-- Confirmación de cancelación (evita confirm/alert nativos) -->
    <div v-if="idViajeConfirmacionCancelacion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">¿Cancelar reserva?</h3>
        <p class="text-neutral-slate mb-6">¿Estás seguro de que deseas cancelar este viaje? Se cobrará el importe completo y, si no hay saldo suficiente en tu cartera, se generará una deuda.</p>
        <div class="flex space-x-3">
          <button @click="confirmarCancelacionViaje" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Sí, cancelar</button>
          <button @click="cancelarConfirmacionCancelacion" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">No, volver</button>
        </div>
      </div>
    </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
const mensajeError = ref('')
const mensajeInfo = ref('')
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import ModalPago from '../../Componentes/ModalPago.vue'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import { useCarteraStore } from '../../Almacenes/almacenCartera.js'

const viajeStore = useViajeStore()
const carteraStore = useCarteraStore()

const filtroSeleccionado = ref('all')


const mostrarModalPago = ref(false)
const viajePago = ref(null)
const mostrarModalValoracion = ref(false)

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

const viajesFiltrados = computed(() => {
  // Aplica el filtro seleccionado y ordena por fecha (más reciente primero)
  let viajes = [...viajeStore.viajesPasajero]

  if (filtroSeleccionado.value !== 'all') {
    viajes = viajes.filter(t => t.estado === filtroSeleccionado.value)
  }

  return viajes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const estadisticasViajes = computed(() => {
  // Estadísticas rápidas para la cabecera
  const total = viajeStore.viajesPasajero.length
  const completados = viajeStore.viajesPasajero.filter(t => t.estado === 'completed').length
  const pendientes = viajeStore.viajesPasajero.filter(t => t.estado === 'pendiente').length
  const cancelados = viajeStore.viajesPasajero.filter(t => t.estado === 'cancelled').length
  const activos = viajeStore.viajesPasajero.filter(t => ['accepted', 'in_progress'].includes(t.estado)).length

  return { total, completados, pendientes, cancelados, activos }
})

const obtenerDistintivoEstado = (estado) => {
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

const abrirConfirmacionCancelacion = async (viajeId) => {
  // Abre el modal de confirmación; no cancela directamente
  mensajeError.value = ''
  mensajeInfo.value = ''
  idViajeConfirmacionCancelacion.value = viajeId
}
const idViajeConfirmacionCancelacion = ref(null)

const confirmarCancelacionViaje = async () => {
  // Confirma la cancelación y refresca cartera/deuda
  const viajeId = idViajeConfirmacionCancelacion.value
  await viajeStore.cancelarViaje(viajeId)
  await carteraStore.obtenerSaldo()
  await carteraStore.obtenerResumenDeuda()
  mensajeInfo.value = 'Reserva cancelada correctamente.'
  setTimeout(() => { mensajeInfo.value = '' }, 4000)
  idViajeConfirmacionCancelacion.value = null
}

const cancelarConfirmacionCancelacion = () => {
  idViajeConfirmacionCancelacion.value = null
}


const abrirModalPago = (viaje) => {
  viajePago.value = viaje
  mostrarModalPago.value = true
}

const manejarPagoExitoso = async (pagoData) => {
  console.log('Pago exitoso:', pagoData)
  await viajeStore.obtenerViajes()
  mensajeInfo.value = 'Pago procesado correctamente'
  setTimeout(() => { mensajeInfo.value = '' }, 4000)
}


const formatearFecha = (cadenaFecha) => {
  const date = new Date(cadenaFecha)
  
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
  viajeStore.obtenerViajes()
  carteraStore.obtenerSaldo()
  carteraStore.obtenerResumenDeuda()
})
</script>
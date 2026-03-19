<template>
  <DisposicionPasajero>
    <!-- Página del pasajero: seguimiento del viaje en tiempo real y cancelación (si aplica) -->
    <div class="max-w-7xl mx-auto">
      <!-- Cabecera con estado resumido -->
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">Seguimiento del Viaje</h1>
            <p class="text-blue-100">Sigue tu taxi en tiempo real por las carreteras de Lanzarote</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 relative z-0">
          <div class="bg-white rounded-xl shadow-sm p-6">
            <!-- Mapa de seguimiento -->
            <div class="h-[500px] rounded-lg overflow-hidden">
              <div v-if="puedeRenderMapa" class="h-full">
                <MapaSeguimiento ref="referenciaMapa" :pickupLat="viaje.pickupLat" :pickupLng="viaje.pickupLng" :dropoffLat="viaje.dropoffLat" :dropoffLng="viaje.dropoffLng" :taxiLat="ubicacionTaxi?.lat" :taxiLng="ubicacionTaxi?.lng" :estado="viaje.estado" />
              </div>
              <div v-else class="h-full flex items-center justify-center bg-neutral-soft">
                <p class="text-sm text-neutral-slate">Cargando mapa…</p>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-1 space-y-4 relative z-10">
          <div class="bg-white rounded-xl shadow-sm p-6">
            <!-- Línea de tiempo del estado del viaje -->
            <h3 class="font-semibold text-neutral-dark mb-4 flex items-center gap-2">
              <svg class="w-6 h-6" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="svgIconoTaxi"></svg>
              Estado del viaje
            </h3>
            
            <div class="space-y-4">
              <div class="relative">
                <div class="absolute left-2 top-2 bottom-2 w-0.5 bg-neutral-volcanic"></div>
                
                <div class="relative pl-8 pb-4">
                  <div class="absolute left-0 w-4 h-4 rounded-full" :class="viaje?.estado === 'pendiente' ? 'bg-yellow-500 ring-4 ring-yellow-100' : 'bg-green-500'">
                  </div>
                  <p class="text-sm font-medium">Solicitud enviada</p>
                  <p class="text-xs text-neutral-slate">{{ formatearFecha(viaje?.created_at) }}</p>
                </div>

                <div class="relative pl-8 pb-4">
                  <div class="absolute left-0 w-4 h-4 rounded-full" :class="viaje?.estado === 'accepted' || viaje?.estado === 'in_progress' || viaje?.estado === 'completed' ? 'bg-green-500' : 'bg-neutral-volcanic'">
                  </div>
                  <p class="text-sm font-medium">Taxista asignado</p>
                  <p v-if="viaje?.conductorName" class="text-xs text-neutral-slate">
                    {{ viaje.conductorName }}
                  </p>
                </div>

                <div class="relative pl-8 pb-4">
                  <div class="absolute left-0 w-4 h-4 rounded-full" :class="viaje?.estado === 'in_progress' || viaje?.estado === 'completed' ? 'bg-green-500' : 'bg-neutral-volcanic'">
                  </div>
                  <p class="text-sm font-medium">Viaje en curso</p>
                  <p v-if="viaje?.estado === 'in_progress'" class="text-xs text-neutral-slate">
                    {{ ubicacionTaxi ? 'Taxi en movimiento' : 'Esperando al taxi' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6">
            <!-- Detalles resumidos del viaje (origen, destino, distancia y precio) -->
            <h3 class="font-semibold text-neutral-dark mb-4">Detalles del viaje</h3>
            
            <div class="space-y-3">
              <div>
                <p class="text-xs text-neutral-slate">Origen</p>
                <p class="font-medium">{{ viaje?.pickup_address || viaje?.pickup }}</p>
              </div>
              
              <div>
                <p class="text-xs text-neutral-slate">Destino</p>
                <p class="font-medium">{{ viaje?.dropoff_address || viaje?.dropoff }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4 pt-2 border-t border-neutral-volcanic">
                <div>
                  <p class="text-xs text-neutral-slate">Distancia</p>
                  <p class="font-semibold text-lanzarote-blue">{{ viaje?.distance || 0 }} km</p>

                  <button type="button" @click="volverAReservas" class="mt-3 w-full border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">
                    Volver atrás
                  </button>
                </div>
                <div>
                  <p class="text-xs text-neutral-slate">Precio</p>
                  <p class="font-semibold text-lanzarote-blue">{{ viaje?.price || 0 }} €</p>
                </div>
              </div>

              <div v-if="['pendiente', 'accepted'].includes(viaje?.estado)" class="mt-4">
                <!-- Cancelación: solo disponible mientras el viaje esté pendiente o aceptado -->
                <button type="button" @click.stop.prevent="abrirConfirmacionCancelacion" class="w-full bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition-colors">
                  Cancelar solicitud
                </button>
                <p v-if="viaje?.estado === 'pendiente'" class="text-xs text-center text-neutral-slate mt-2">
                  Buscando taxista disponible...
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación de cancelación (evita confirm/alert nativos) -->
    <div v-if="mostrarConfirmacionCancelacion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">¿Confirmar cancelación?</h3>
        <p class="text-neutral-slate mb-6">¿Confirmas la cancelación de este viaje? Se cobrará el importe completo y, si no hay saldo suficiente en tu cartera virtual, se generará una deuda.</p>
        <div class="flex space-x-3">
          <button @click="confirmarCancelacionViaje" class="flex-1 bg-lanzarote-blue text-white py-2 rounded-lg hover:bg-lanzarote-yellow hover:text-black">Sí, cancelar</button>
          <button @click="cancelarCancelacionViaje" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">Cancelar</button>
        </div>
      </div>
    </div>
  </DisposicionPasajero>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router as inertiaRouter } from '@inertiajs/vue3'
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import MapaSeguimiento from '../../Componentes/MapaSeguimiento.vue'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import axios from 'axios'
import '../../../css/seguimiento.css'

import svgTaxiFront from 'bootstrap-icons/icons/taxi-front.svg?raw'

const props = defineProps({
  viajeId: {
    type: [Number, String],
    required: true,
  }
})

const viajeStore = useViajeStore()
const referenciaMapa = ref(null)

// Normaliza el SVG (bootstrap-icons) para poder inyectarlo con v-html
const extraerInteriorSvg = (raw) => raw
  .replace(/^<svg[^>]*>/i, '')
  .replace(/<\/svg>\s*$/i, '')
  .trim()

const svgIconoTaxi = extraerInteriorSvg(svgTaxiFront)

const viaje = ref(null)
const ubicacionTaxi = ref(null)
let idIntervaloActualizacion = null
const mostrarConfirmacionCancelacion = ref(false)

const puedeRenderMapa = computed(() => {
  // Evita renderizar el mapa si faltan coordenadas
  const v = viaje.value
  if (!v) return false
  const hasPickup = Number.isFinite(v.pickupLat) && Number.isFinite(v.pickupLng)
  const hasDropoff = Number.isFinite(v.dropoffLat) && Number.isFinite(v.dropoffLng)
  return hasPickup && hasDropoff
})

const viajeIdNumber = computed(() => Number(props.viajeId))

const viajeFromStore = computed(() => {
  return viajeStore.viajesPasajero.find(t => t.id === viajeIdNumber.value) || null
})

const cargarViaje = async () => {
  // Carga el viaje desde el store; si no existe, refresca y redirige si sigue sin encontrarse
  viaje.value = viajeFromStore.value

  if (!viaje.value) {
    try {
      await viajeStore.obtenerViajes()
      viaje.value = viajeFromStore.value
    } catch (error) {
      console.error('Error cargando viajes:', error)
    }
  }

  if (!viaje.value) {
    inertiaRouter.visit('/pasajero/reservas')
  }
}

const obtenerTextoEstado = (estado) => {
  const estados = {
    'pendiente': 'Buscando taxista',
    'accepted': 'Taxista en camino',
    'in_progress': 'Viaje en curso',
    'completed': 'Finalizado',
    'cancelled': 'Cancelado'
  }
  return estados[estado] || estado
}

const volverAReservas = () => {
  inertiaRouter.visit('/pasajero/reservas')
}

const formatearFecha = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit'
  })
}

const refrescarUbicacionTaxi = async () => {
  // Consulta periódica de la ubicación del taxi (solo cuando tiene sentido)
  if (!viaje.value) return
  if (!['accepted', 'in_progress'].includes(viaje.value.estado)) return

  try {
    const response = await axios.get(`/api/viajes/${viaje.value.id}/track`)
    ubicacionTaxi.value = response.data?.ubicacion || null
  } catch (error) {
  }
}

const abrirConfirmacionCancelacion = () => {
  // Abre el modal de confirmación
  mostrarConfirmacionCancelacion.value = true
}

const confirmarCancelacionViaje = async () => {
  // Confirma la cancelación y vuelve al listado
  try {
    await viajeStore.cancelarViaje(viaje.value.id)
    mostrarConfirmacionCancelacion.value = false
    inertiaRouter.visit('/pasajero/reservas')
  } catch (error) {
    console.error('Error cancelando viaje:', error)
  }
}

const cancelarCancelacionViaje = () => {
  mostrarConfirmacionCancelacion.value = false
}

onMounted(() => {
  // Carga inicial + polling de estado/ubicación
  cargarViaje()

  idIntervaloActualizacion = setInterval(() => {
    viaje.value = viajeFromStore.value
    refrescarUbicacionTaxi()
  }, 3000)
})

onUnmounted(() => {
  // Limpieza del intervalo al salir de la página
  if (idIntervaloActualizacion) {
    clearInterval(idIntervaloActualizacion)
  }
})

watch(viajeFromStore, (next) => {
  viaje.value = next
})
</script>
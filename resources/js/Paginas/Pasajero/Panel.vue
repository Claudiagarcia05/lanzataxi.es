<template>
  <DisposicionPasajero>
    <div class="max-w-7xl mx-auto">
      <!--
        Nueva reserva (pasajero).
        - Captura origen/destino y opcionalmente fecha/hora programada.
        - Usa el componente MapaTaxi para calcular distancia y para “picar” ubicaciones.
        - Geocodifica direcciones (Nominatim) y limita ubicaciones a Lanzarote.
        - Calcula un precio estimado en cliente (aproximado) para mostrarlo antes de pedir.

        Importante:
        - El precio final y validaciones definitivas deben imponerse en backend.
        - Las llamadas a Nominatim deben usarse con moderación (por eso hay debounce).
      -->
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">{{ t('passenger.newBooking.heroTitle') }}</h1>
        <p class="text-blue-100">{{ t('passenger.newBooking.heroSubtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center space-x-2 mb-6">
              <svg class="w-6 h-6 text-neutral-dark" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="iconoTaxiSvg"></svg>
              <h2 class="text-xl font-bold text-neutral-dark">LanzaTaxi</h2>
              <span class="text-neutral-slate">{{ t('passenger.newBooking.whereTo') }}</span>
            </div>

            <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
              <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
            </div>
            <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
              <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
            </div>

            <form @submit.prevent="enviarReserva" class="space-y-6">
              <div class="border-b border-neutral-volcanic pb-6">
                <h3 class="font-semibold text-neutral-dark mb-4">{{ t('passenger.newBooking.tripDetails') }}</h3>
                
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-neutral-dark mb-1">
                      {{ t('passenger.newBooking.pickupLabel') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                      <input v-model="formularioReserva.pickupAddress" type="text" required class="flex-1 px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: Calle Real 45, Arrecife"/>
                      <button type="button" class="ml-1 px-3 py-2 rounded-lg bg-lanzarote-blue text-white hover:bg-lanzarote-yellow hover:text-black transition-colors text-xs" :title="t('passenger.newBooking.myLocation')" @click="obtenerUbicacionUsuario">
                        {{ t('passenger.newBooking.myLocation') }}
                      </button>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-neutral-dark mb-1">
                      {{ t('passenger.newBooking.dropoffLabel') }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="formularioReserva.dropoffAddress" type="text" required class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: Aeropuerto César Manrique"/>
                  </div>
                </div>
              </div>

              <div class="border-b border-neutral-volcanic pb-6">
                <h3 class="font-semibold text-neutral-dark mb-4">{{ t('passenger.newBooking.dateTime') }}</h3>
                
                <div class="space-y-4">
                  <div class="flex items-center gap-3">
                    <input v-model="formularioReserva.isScheduled" type="checkbox" id="scheduled" class="w-4 h-4 text-lanzarote-blue rounded"/>
                    <label for="scheduled" class="text-sm text-neutral-slate">
                      {{ t('passenger.newBooking.scheduleLater') }}
                    </label>
                  </div>

                  <div v-if="formularioReserva.isScheduled" class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-neutral-dark mb-1">
                        {{ t('passenger.newBooking.pickupDate') }} <span class="text-red-500">*</span>
                      </label>
                      <input v-model="formularioReserva.viajeDate" type="date" :min="new Date().toISOString().split('T')[0]" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue"/>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-neutral-dark mb-1">
                        {{ t('passenger.newBooking.pickupTime') }} <span class="text-red-500">*</span>
                      </label>
                      <input v-model="formularioReserva.viajeTime" type="time" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue"/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="border-b border-neutral-volcanic pb-6">
                <h3 class="font-semibold text-neutral-dark mb-4">{{ t('passenger.newBooking.options') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('passenger.newBooking.passengers') }}</label>
                    <select v-model.number="formularioReserva.pasajeros" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue bg-white">
                      <option v-for="n in 6" :key="n" :value="n">{{ n }} pasajero{{ n !== 1 ? 's' : '' }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('passenger.newBooking.luggage') }}</label>
                    <select v-model.number="formularioReserva.luggage" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue bg-white">
                      <option :value="0">{{ t('passenger.newBooking.noLuggage') }}</option>
                      <option :value="1">{{ t('passenger.newBooking.luggage1') }}</option>
                      <option :value="2">{{ t('passenger.newBooking.luggage2') }}</option>
                      <option :value="3">{{ t('passenger.newBooking.luggage3') }}</option>
                      <option :value="4">{{ t('passenger.newBooking.luggage4') }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('passenger.newBooking.paymentMethod') }}</label>
                    <select v-model="formularioReserva.pagoMethod" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue bg-white">
                      <option value="efectivo">{{ t('passenger.newBooking.cash') }}</option>
                      <option value="wallet">{{ t('passenger.newBooking.myWallet', { balance: saldoCartera.toFixed(2) }) }}</option>
                      <option value="tarjeta">{{ t('passenger.newBooking.card') }}</option>
                    </select>
                  </div>
                </div>

                <div v-if="formularioReserva.pagoMethod === 'wallet' && totalEstimadoPagar > saldoCartera" class="mt-3 p-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600">
                    {{ t('passenger.newBooking.insufficientBalance', { amount: (totalEstimadoPagar - saldoCartera).toFixed(2) }) }}
                    </p>
                </div>
              </div>

              <div class="border-b border-neutral-volcanic pb-6">
                <h3 class="font-semibold text-neutral-dark mb-4">{{ t('passenger.newBooking.notes') }}</h3>
                <textarea v-model="formularioReserva.notes" rows="3" class="w-full px-4 py-3 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue resize-none" :placeholder="t('passenger.newBooking.notesPlaceholder')"></textarea>
              </div>

              <div v-if="formularioReserva.distance > 0" class="bg-neutral-soft p-4 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-neutral-slate">{{ t('passenger.newBooking.estimatedDistance') }}</span>
                  <span class="font-semibold">{{ formularioReserva.distance }} km</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-neutral-volcanic">
                  <span class="text-lg font-bold text-neutral-dark">{{ t('passenger.newBooking.estimatedPrice') }}</span>
                  <span class="text-2xl font-bold text-lanzarote-blue">{{ formularioReserva.estimatedPrice.toFixed(2) }} €</span>
                </div>
                <div v-if="deudaPendiente > 0" class="flex justify-between items-center mt-2">
                  <span class="text-sm text-neutral-slate">{{ t('passenger.newBooking.pendingDebt') }}</span>
                  <span class="font-semibold text-neutral-dark">{{ deudaPendiente.toFixed(2) }} €</span>
                </div>
                <div v-if="deudaPendiente > 0" class="flex justify-between items-center pt-2 border-t border-neutral-volcanic mt-2">
                  <span class="text-lg font-bold text-neutral-dark">{{ t('passenger.newBooking.totalToPay') }}</span>
                  <span class="text-2xl font-bold text-lanzarote-blue">{{ totalEstimadoPagar.toFixed(2) }} €</span>
                </div>
              </div>

              <button type="submit" :disabled="!puedeEnviar" class="w-full bg-lanzarote-blue text-white font-bold py-4 px-6 rounded-xl text-lg hover:bg-lanzarote-yellow hover:text-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                {{ t('passenger.newBooking.confirm') }}
              </button>
            </form>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
            <MapaTaxi :pickupLat="formularioReserva.pickupLat" :pickupLng="formularioReserva.pickupLng" :dropoffLat="formularioReserva.dropoffLat" :dropoffLng="formularioReserva.dropoffLng" @distance-calculated="(distance) => formularioReserva.distance = Number.parseFloat(distance) || 0" @location-found="manejarUbicacionUsuario"/>
          </div>
        </div>
      </div>
    </div>
  </DisposicionPasajero>
</template>


<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
const mensajeError = ref('')
const mensajeInfo = ref('')
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import { useCarteraStore } from '../../Almacenes/almacenCartera.js'
import { apiFetch } from '../../Servicios/apiFetch'

import svgTaxiFront from 'bootstrap-icons/icons/taxi-front.svg?raw'

const authStore = useAuthStore()
const viajeStore = useViajeStore()
const carteraStore = useCarteraStore()
const { t } = useI18n()
const MapaTaxi = defineAsyncComponent(() => import('../../Componentes/MapaTaxi.vue'))

const normalizarSvg = (raw) => raw
  .replace(/^<svg[^>]*>/i, '')
  .replace(/<\/svg>\s*$/i, '')
  .trim()

const iconoTaxiSvg = normalizarSvg(svgTaxiFront)

// Bounding box aproximado para restringir coordenadas a Lanzarote.
// Se usa para validar geolocalización y resultados de geocodificación.
const LANZAROTE_BOUNDS = {
  south: 28.85,
  west: -13.95,
  north: 29.35,
  east: -13.20,
}

const estaEnLanzarote = (lat, lng) => {
  // Validación rápida de rango (no reemplaza una validación geoespacial exacta).
  return Number.isFinite(lat)
    && Number.isFinite(lng)
    && lat >= LANZAROTE_BOUNDS.south
    && lat <= LANZAROTE_BOUNDS.north
    && lng >= LANZAROTE_BOUNDS.west
    && lng <= LANZAROTE_BOUNDS.east
}

const viewboxLanzarote = () => {
  // `viewbox` para Nominatim: limita resultados al área de interés.
  return `${LANZAROTE_BOUNDS.west},${LANZAROTE_BOUNDS.north},${LANZAROTE_BOUNDS.east},${LANZAROTE_BOUNDS.south}`
}

const manejarUbicacionUsuario = (ubicacion) => {
  // Callback del mapa: cuando se encuentra una ubicación/dirección.
  // Si trae lat/lng, validamos que esté en Lanzarote.
  if (ubicacion && ubicacion.address) {
    if (ubicacion.lat && ubicacion.lng) {
      const lat = Number.parseFloat(ubicacion.lat)
      const lng = Number.parseFloat(ubicacion.lng)
      if (!estaEnLanzarote(lat, lng)) {
        mensajeError.value = 'Solo se admiten direcciones y ubicaciones dentro de Lanzarote.'

        return
      }
    }

    formularioReserva.value.pickupAddress = ubicacion.address
    if (ubicacion.lat && ubicacion.lng) {
      formularioReserva.value.pickupLat = ubicacion.lat
      formularioReserva.value.pickupLng = ubicacion.lng
    }
  }
}

let temporizadorGeocodificacionRecogida = null
let temporizadorGeocodificacionDestino = null
const suprimirWatcherRecogida = ref(false)
const suprimirWatcherDestino = ref(false)

const geocodificarDireccion = async (address) => {
  // Geocodifica una dirección con Nominatim.
  // Nota: Nominatim tiene políticas de uso; evitamos bombardear con cada tecla (debounce abajo).
  const q = (address || '').trim()
  if (!q) return null

  // Usamos el proxy backend para evitar CORS en producción.
  const response = await apiFetch('/api/geocoding/search', {
    params: {
      q,
      limit: 5,
      // El backend fuerza los parámetros (format/json, bounded/viewbox Lanzarote, etc.).
    },
  })

  const candidatos = Array.isArray(response) ? response : []
  for (const item of candidatos) {
    const lat = Number.parseFloat(item?.lat)
    const lng = Number.parseFloat(item?.lon)
    if (!estaEnLanzarote(lat, lng)) continue

    return { lat, lng, displayName: item?.display_name }
  }

  return null
}

const establecerCoordenadasRecogida = ({ lat, lng }) => {
  // Al escribir coordenadas desde geocodificación, suprimimos el watcher para evitar bucles.
  suprimirWatcherRecogida.value = true
  formularioReserva.value.pickupLat = lat
  formularioReserva.value.pickupLng = lng
}

const establecerCoordenadasDestino = ({ lat, lng }) => {
  // Igual que recogida: se marca para que el watcher no “reinicie” el estado.
  suprimirWatcherDestino.value = true
  formularioReserva.value.dropoffLat = lat
  formularioReserva.value.dropoffLng = lng
}

const obtenerUbicacionUsuario = async () => {
  // Geolocalización del navegador + reverse geocoding.
  // Se usa para rellenar origen con “Mi ubicación”.
  mensajeError.value = ''
  mensajeInfo.value = ''
  if (!navigator.geolocation) {
    mensajeError.value = 'La geolocalización no está soportada en este navegador.'

    return
  }
  navigator.geolocation.getCurrentPosition(async (position) => {
    const lat = position.coords.latitude
    const lng = position.coords.longitude

    if (!estaEnLanzarote(lat, lng)) {
      mensajeError.value = 'Tu ubicación actual parece estar fuera de Lanzarote.'

      return
    }

    try {
      // Reverse geocoding vía proxy backend para evitar CORS.
      const response = await apiFetch('/api/geocoding/reverse', {
        params: {
          lat,
          lon: lng,
        },
      })
      const address = response?.display_name || `${lat}, ${lng}`
      manejarUbicacionUsuario({ address, lat, lng })

      formularioReserva.value.distance = 0
      formularioReserva.value.estimatedPrice = 0

      mensajeInfo.value = 'Ubicación obtenida correctamente.'
      setTimeout(() => { mensajeInfo.value = '' }, 4000)
    } catch (e) {
      formularioReserva.value.pickupAddress = `${lat}, ${lng}`
      formularioReserva.value.pickupLat = lat
      formularioReserva.value.pickupLng = lng

      formularioReserva.value.distance = 0
      formularioReserva.value.estimatedPrice = 0

      mensajeInfo.value = 'Ubicación obtenida, pero no se pudo determinar la dirección exacta.'
      setTimeout(() => { mensajeInfo.value = '' }, 4000)
    }
  }, (error) => {
    mensajeError.value = 'No se pudo obtener la ubicación'
  })
}

const obtenerTextoEstado = (estado) => {
  // (Helper de UI) Traducción rápida de estado a texto.
  const estados = {
    'pendiente': 'Buscando taxista',
    'accepted': 'Taxista en camino',
    'in_progress': 'Viaje en curso',
    'completed': 'Viaje completado',
    'cancelled': 'Cancelado'
  }

  return estados[estado] || estado
}
const formularioReserva = ref({
  pickupAddress: '',
  pickupLat: 28.963,
  pickupLng: -13.550,
  dropoffAddress: '',
  dropoffLat: null,
  dropoffLng: null,
  
  viajeDate: new Date().toISOString().split('T')[0],
  viajeTime: new Date().toTimeString().slice(0, 5),
  isScheduled: false,
  
  pasajeros: 1,
  luggage: 0,
  pagoMethod: 'efectivo',
  
  notes: '',
  
  distance: 0,
  estimatedPrice: 0
})

const municipios = [
  'Arrecife', 'Puerto del Carmen', 'Costa Teguise', 'Playa Blanca', 'Haria', 'Teguise', 'Aeropuerto', 'Puerto Calero'
]

const trayectosFijos = [
  { origen: 'Aeropuerto', destino: 'Arrecife', dia: 10, noche: 14 },
  { origen: 'Aeropuerto', destino: 'Puerto del Carmen', dia: 12, noche: 18 },
  { origen: 'Aeropuerto', destino: 'Costa Teguise', dia: 20, noche: 24 },
  { origen: 'Arrecife', destino: 'Playa Blanca', dia: 45, noche: 50 },
  { origen: 'Puerto Calero', destino: 'Aeropuerto', dia: 45.86, noche: null },
]

const esHorarioNocturno = () => {
  // Ventana nocturna simple (tarifas nocturnas).
  const hora = parseInt(formularioReserva.value.viajeTime.split(':')[0])

  return hora >= 22 || hora < 6
}

const obtenerMunicipio = (direccion) => {
  // Heurística muy simple para mapear dirección → municipio.
  // Si no detecta nada, cae a Arrecife.
  if (!direccion) return 'Arrecife';
  for (const m of municipios) {
    if (direccion.toLowerCase().includes(m.toLowerCase())) return m;
  }

  return 'Arrecife';
}

const calcularPrecioEstimado = () => {
  // Precio estimado en cliente.
  // - Primero intenta coincidir con trayectos fijos.
  // - Si no, aplica una fórmula por km (con bajada de bandera) según origen/destino.
  // Importante: es una estimación para UX; el precio final debe validarse en backend.
  const distancia = Number.parseFloat(formularioReserva.value.distance)
  const tieneDistancia = Number.isFinite(distancia) && distancia > 0
  const tieneDirecciones = Boolean(formularioReserva.value.pickupAddress?.trim()) && Boolean(formularioReserva.value.dropoffAddress?.trim())

  if (!tieneDirecciones || !tieneDistancia) {
    formularioReserva.value.estimatedPrice = 0

    return
  }

  const origen = obtenerMunicipio(formularioReserva.value.pickupAddress)
  const destino = obtenerMunicipio(formularioReserva.value.dropoffAddress)
  const esNoche = esHorarioNocturno()

  for (const t of trayectosFijos) {
    if ((t.origen === origen && t.destino === destino) || (t.destino === origen && t.origen === destino)) {
      formularioReserva.value.estimatedPrice = esNoche && t.noche ? t.noche : t.dia

      return
    }
  }

  if (origen === 'Arrecife' && destino === 'Arrecife') {
    const bajada = esNoche ? 3.65 : 3.05
    const precioKm = esNoche ? 0.92 : 0.80
    formularioReserva.value.estimatedPrice = Math.round((bajada + (distancia * precioKm)) * 100) / 100

    return
  }

  if (origen === 'Arrecife' || destino === 'Arrecife') {
    const bajada = esNoche ? 3.65 : 3.05
    const precioKm = esNoche ? 0.92 : 0.80
    formularioReserva.value.estimatedPrice = Math.round((bajada + (distancia * precioKm)) * 100) / 100

    return
  }

  const bajada = 3.50
  const precioKm = 1.10
  formularioReserva.value.estimatedPrice = Math.round((bajada + (distancia * precioKm)) * 100) / 100
}

watch([() => formularioReserva.value.distance, () => formularioReserva.value.luggage, () => formularioReserva.value.viajeTime], () => {
  calcularPrecioEstimado()
})

watch(() => formularioReserva.value.pickupAddress, (nuevaDireccion) => {
  // Debounce de geocodificación para origen:
  // - Resetea coordenadas/distancia al cambiar el texto.
  // - Espera 700ms tras dejar de teclear.
  if (suprimirWatcherRecogida.value) {
    suprimirWatcherRecogida.value = false

    return
  }

  formularioReserva.value.pickupLat = null
  formularioReserva.value.pickupLng = null
  formularioReserva.value.distance = 0
  formularioReserva.value.estimatedPrice = 0

  if (temporizadorGeocodificacionRecogida) clearTimeout(temporizadorGeocodificacionRecogida)
  const q = (nuevaDireccion || '').trim()
  if (q.length < 5) return

  temporizadorGeocodificacionRecogida = setTimeout(async () => {
    if ((formularioReserva.value.pickupAddress || '').trim() !== q) return
    try {
      const resultado = await geocodificarDireccion(q)
      if (!resultado) return
      if ((formularioReserva.value.pickupAddress || '').trim() !== q) return
      establecerCoordenadasRecogida(resultado)
    } catch {
    }
  }, 700)
})

watch(() => formularioReserva.value.dropoffAddress, (nuevaDireccion) => {
  // Debounce de geocodificación para destino (misma idea que origen).
  if (suprimirWatcherDestino.value) {
    suprimirWatcherDestino.value = false

    return
  }

  formularioReserva.value.dropoffLat = null
  formularioReserva.value.dropoffLng = null
  formularioReserva.value.distance = 0
  formularioReserva.value.estimatedPrice = 0

  if (temporizadorGeocodificacionDestino) clearTimeout(temporizadorGeocodificacionDestino)
  const q = (nuevaDireccion || '').trim()
  if (q.length < 5) return

  temporizadorGeocodificacionDestino = setTimeout(async () => {
    if ((formularioReserva.value.dropoffAddress || '').trim() !== q) return
    try {
      const resultado = await geocodificarDireccion(q)
      if (!resultado) return
      if ((formularioReserva.value.dropoffAddress || '').trim() !== q) return
      establecerCoordenadasDestino(resultado)
    } catch {
    }
  }, 700)
})

watch([
  () => formularioReserva.value.pickupAddress,
  () => formularioReserva.value.dropoffAddress,
  () => formularioReserva.value.distance,
  () => formularioReserva.value.luggage,
  () => formularioReserva.value.viajeTime,
], () => {
  calcularPrecioEstimado()
})

const enviarReserva = async () => {
  // Validaciones previas (UX): evita duplicar viajes activos, requiere coordenadas y distancia,
  // y chequea saldo/deuda si el método de pago es cartera.
  mensajeError.value = ''
  if (viajeActivo.value) {
    mensajeError.value = 'No puedes pedir un taxi nuevo mientras tengas un viaje pendiente, aceptado o en curso.'

    return
  }

  if (deudaPendiente.value > 0 && saldoCartera.value < deudaPendiente.value) {
    // Regla de negocio: no permitir nueva reserva si hay deuda pendiente no cubierta.
    mensajeError.value = `Tienes una deuda pendiente de ${deudaPendiente.value.toFixed(2)}€. Anade saldo a tu cartera para poder solicitar un nuevo taxi.`
    
    return
  }

  if (!formularioReserva.value.pickupAddress || !formularioReserva.value.dropoffAddress) {
    mensajeError.value = 'Por favor completa los datos de origen y destino.'

    return
  }

  const latRecogida = Number.parseFloat(formularioReserva.value.pickupLat)
  const lngRecogida = Number.parseFloat(formularioReserva.value.pickupLng)
  const latDestino = Number.parseFloat(formularioReserva.value.dropoffLat)
  const lngDestino = Number.parseFloat(formularioReserva.value.dropoffLng)

  if (!Number.isFinite(latRecogida) || !Number.isFinite(lngRecogida)) {
    mensajeError.value = 'No se pudo localizar la dirección de origen. Prueba con una dirección más específica o usa "Mi ubicación".'
    
    return
  }

  if (!Number.isFinite(latDestino) || !Number.isFinite(lngDestino)) {
    mensajeError.value = 'No se pudo localizar la dirección de destino. Prueba con una dirección más específica.'

    return
  }

  const distancia = Number.parseFloat(formularioReserva.value.distance)
  if (!Number.isFinite(distancia) || distancia <= 0) {
    mensajeError.value = 'No se pudo calcular la distancia del viaje todavía. Espera un momento e inténtalo de nuevo.'

    return
  }

  if (formularioReserva.value.pagoMethod === 'wallet' && totalEstimadoPagar.value > carteraStore.saldo) {
    mensajeError.value = 'No tienes suficiente saldo en tu cartera virtual. Añade dinero o elige otro método de pago.'

    return
  }
  
  const datosViaje = {
    // Payload esperado por backend.
    pickup_address: formularioReserva.value.pickupAddress,
    pickup_lat: latRecogida,
    pickup_lng: lngRecogida,
    dropoff_address: formularioReserva.value.dropoffAddress,
    dropoff_lat: latDestino,
    dropoff_lng: lngDestino,
    distance: distancia,
    scheduled_for: formularioReserva.value.isScheduled 
      // Formato simple fecha+hora; el backend debe parsear/validar.
      ? `${formularioReserva.value.viajeDate} ${formularioReserva.value.viajeTime}` 
      : null,
    pasajeros: formularioReserva.value.pasajeros,
    luggage: formularioReserva.value.luggage,
    pago_method: formularioReserva.value.pagoMethod,
    notes: formularioReserva.value.notes
  }
  
  const resultado = await viajeStore.crearViaje(datosViaje)

  console.log('Resultado al crear viaje:', resultado)

  if (resultado.success) {
    // Tras crear el viaje, refrescamos stores relevantes y redirigimos al historial.
    console.log('Viaje creado correctamente:', resultado.viaje)
    console.log('Cargando viajes...')
    await viajeStore.obtenerViajes()
    console.log('Viajes cargados:', viajeStore.viajesPasajero)
    await carteraStore.obtenerResumenDeuda()
    reiniciarFormulario()
    window.location.href = '/pasajero/reservas'
  } else {
    console.error('Error al crear viaje:', resultado.error)
    if (resultado.message) {
      mensajeError.value = resultado.message
    } else {
      mensajeError.value = 'Error no dispone de suficiente saldo en la Cartera'
    }
  }
}

const reiniciarFormulario = () => {
  // Reinicia el formulario a valores por defecto (incluye centro del mapa en Lanzarote).
  formularioReserva.value = {
    pickupAddress: '',
    pickupLat: 28.963,
    pickupLng: -13.550,
    dropoffAddress: '',
    dropoffLat: null,
    dropoffLng: null,
    viajeDate: new Date().toISOString().split('T')[0],
    viajeTime: new Date().toTimeString().slice(0, 5),
    isScheduled: false,
    pasajeros: 1,
    luggage: 0,
    pagoMethod: 'efectivo',
    notes: '',
    distance: 0,
    estimatedPrice: 0
  }
}

const viajeActivo = computed(() => {
  // Un pasajero sólo puede tener 1 viaje “activo” (pendiente/aceptado/en curso).
  return viajeStore.viajesPasajero.find(t => ['pendiente', 'accepted', 'in_progress'].includes(t.estado))
})

const saldoCartera = computed(() => carteraStore.saldo)
const deudaPendiente = computed(() => carteraStore.deudaPendiente)
const totalEstimadoPagar = computed(() => formularioReserva.value.estimatedPrice + deudaPendiente.value)

const puedeEnviar = computed(() => {
  // Habilita el botón “Confirmar” sólo si se cumplen reglas mínimas.
  const tieneDirecciones = Boolean(formularioReserva.value.pickupAddress) && Boolean(formularioReserva.value.dropoffAddress)
  if (!tieneDirecciones) return false
  if (viajeActivo.value) return false
  if (deudaPendiente.value > 0 && saldoCartera.value < deudaPendiente.value) return false
  if (formularioReserva.value.pagoMethod === 'wallet' && totalEstimadoPagar.value > saldoCartera.value) return false

  return true
})

onMounted(() => {
  carteraStore.obtenerSaldo()
  carteraStore.obtenerResumenDeuda()
})
</script>
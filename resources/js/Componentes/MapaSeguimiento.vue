<template>
  <div class="map-container">
    <div ref="mapElement" class="map"></div>

    <!-- Estado de búsqueda -->
    <div v-if="estado === 'pendiente'" class="searching-overlay">
      <div class="bg-white rounded-lg p-4 text-center">
        <div class="animate-spin text-3xl mb-2">🔄</div>
        <p class="font-medium">Buscando taxista disponible</p>
        <p class="text-sm text-neutral-slate mt-1">Espera mientras encontramos un taxi cerca de ti</p>
      </div>
    </div>

    <!-- Información de seguimiento -->
    <div v-if="estado === 'accepted' && taxiLocation" class="taxi-info">
      <div class="bg-white rounded-lg p-3 shadow-lg">
        <div class="flex items-center gap-2">
          <span class="text-2xl">🚕</span>
          <div>
            <p class="text-sm font-medium">Taxista en camino</p>
            <p class="text-xs text-neutral-slate">Llegada estimada: {{ tiempoLlegada }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'
import 'leaflet-routing-machine'
import '../../css/seguimiento.css'

const normalizeOsrmServiceUrl = (url) => {
  if (typeof url !== 'string') return null
  const trimmed = url.trim()
  if (!trimmed) return null
  return trimmed.endsWith('/') ? trimmed : `${trimmed}/`
}

const OSRM_SERVICE_URL = normalizeOsrmServiceUrl(import.meta.env.VITE_OSRM_SERVICE_URL)
  || 'https://router.project-osrm.org/route/v1/'

const props = defineProps({
  pickupLat: { type: Number, default: null },
  pickupLng: { type: Number, default: null },
  dropoffLat: { type: Number, default: null },
  dropoffLng: { type: Number, default: null },
  taxiLat: { type: Number, default: null },
  taxiLng: { type: Number, default: null },
  estado: { type: String, default: 'pendiente' }
})

const emit = defineEmits(['taxi-aceptado'])

const mapElement = ref(null)
let map = null
let routingControl = null
let pickupMarker = null
let dropoffMarker = null
let taxiMarker = null

// Tiempo estimado de llegada (simulado)
const tiempoLlegada = computed(() => {
  if (!props.taxiLat || !props.taxiLng || !props.pickupLat) return 'calculando...'
  
  // Calcular distancia aproximada (esto debería venir de la ruta real)
  const distancia = Math.sqrt(
    Math.pow(props.taxiLat - props.pickupLat, 2) + 
    Math.pow(props.taxiLng - props.pickupLng, 2)
  ) * 111 // Aproximación a km
  
  const minutos = Math.round(distancia * 3) // Aprox 3 min por km
  return `${minutos} min`
})

// Iconos personalizados
const createIcon = (emoji, className = '') => {
  return L.divIcon({
    html: `<div class="marker-emoji ${className}">${emoji}</div>`,
    className: 'custom-marker',
    iconSize: [32, 32],
    popupAnchor: [0, -16]
  })
}

const icons = {
  pickup: createIcon('📍'),
  dropoff: createIcon('🏁'),
  taxi: createIcon('🚕', 'taxi-marker')
}

// Inicializar mapa
const initMap = async () => {
  if (!mapElement.value || map) return

  const hasPickup = Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)
  if (!hasPickup) return

  await nextTick()

  map = L.map(mapElement.value).setView([props.pickupLat, props.pickupLng], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map)

  // Marcador origen
  pickupMarker = L.marker([props.pickupLat, props.pickupLng], {
    icon: icons.pickup
  }).addTo(map).bindPopup('Origen')

  // Marcador destino (solo si hay coordenadas válidas)
  const hasDropoff = Number.isFinite(props.dropoffLat) && Number.isFinite(props.dropoffLng)
  if (hasDropoff) {
    dropoffMarker = L.marker([props.dropoffLat, props.dropoffLng], {
      icon: icons.dropoff
    }).addTo(map).bindPopup('Destino')

    // Calcular ruta
    calculateRoute()
  }

  // Si hay ubicación del taxi, mostrarlo
  if (props.taxiLat && props.taxiLng) {
    addTaxiMarker()
  }
}

// Calcular ruta entre origen y destino
const calculateRoute = () => {
  if (!map) return
  const hasPickup = Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)
  const hasDropoff = Number.isFinite(props.dropoffLat) && Number.isFinite(props.dropoffLng)
  if (!hasPickup || !hasDropoff) return

  if (routingControl) {
    map.removeControl(routingControl)
  }

  routingControl = L.Routing.control({
    waypoints: [
      L.latLng(props.pickupLat, props.pickupLng),
      L.latLng(props.dropoffLat, props.dropoffLng)
    ],
    router: L.Routing.osrmv1({
      serviceUrl: OSRM_SERVICE_URL,
      profile: 'car'
    }),
    lineOptions: {
      styles: [{ color: '#3b82f6', weight: 5 }],
      addWaypoints: false
    },
    show: false,
    createMarker: () => null
  }).addTo(map)

  routingControl.on('routesfound', function(e) {
    const route = e?.routes?.[0]
    if (!route) return

    const bounds = route.bounds
    if (bounds && typeof bounds.isValid === 'function' && bounds.isValid()) {
      map.fitBounds(bounds, { padding: [50, 50] })
      return
    }

    const coordinates = Array.isArray(route.coordinates) ? route.coordinates : []
    if (coordinates.length) {
      const computedBounds = L.latLngBounds(coordinates)
      if (computedBounds.isValid()) {
        map.fitBounds(computedBounds, { padding: [50, 50] })
      }
    }
  })

  routingControl.on('routingerror', function(err) {
    console.error('Routing error:', err)
  })
}

// Añadir marcador del taxi
const addTaxiMarker = () => {
  if (!map) return
  if (!Number.isFinite(props.taxiLat) || !Number.isFinite(props.taxiLng)) return

  if (taxiMarker) {
    taxiMarker.setLatLng([props.taxiLat, props.taxiLng])
  } else {
    taxiMarker = L.marker([props.taxiLat, props.taxiLng], { 
      icon: icons.taxi,
      zIndexOffset: 1000
    }).addTo(map)
    
    // Emitir que el taxi ha sido aceptado/ubicado
    emit('taxi-aceptado', { lat: props.taxiLat, lng: props.taxiLng })
  }
}

// Watchers
watch(() => [props.taxiLat, props.taxiLng], ([newLat, newLng]) => {
  if (Number.isFinite(newLat) && Number.isFinite(newLng)) {
    addTaxiMarker()
  }
})

watch(() => [props.pickupLat, props.pickupLng, props.dropoffLat, props.dropoffLng], () => {
  const hasPickup = Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)
  const hasDropoff = Number.isFinite(props.dropoffLat) && Number.isFinite(props.dropoffLng)

  if (!map) {
    if (hasPickup) initMap()
    return
  }

  if (hasPickup && pickupMarker) {
    pickupMarker.setLatLng([props.pickupLat, props.pickupLng])
  }

  if (hasDropoff) {
    if (dropoffMarker) {
      dropoffMarker.setLatLng([props.dropoffLat, props.dropoffLng])
    } else {
      dropoffMarker = L.marker([props.dropoffLat, props.dropoffLng], {
        icon: icons.dropoff
      }).addTo(map).bindPopup('Destino')
    }
    calculateRoute()
  }
})

// Lifecycle
onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (map) {
    map.remove()
  }
})
</script>

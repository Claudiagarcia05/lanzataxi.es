<template>
  <div class="map-container">
    <div ref="mapElement" class="map"></div>
    
    <div class="map-controls">
      <button @click="centerOnUser" class="control-btn" title="Centrar en mi ubicación">
        <svg class="w-5 h-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="iconos.geoAlt"></svg>
      </button>
      <button @click="toggleSimulation" class="control-btn" :class="{ 'active': isSimulating }" :title="isSimulating ? 'Detener simulación' : 'Simular viaje'">
        <svg v-if="isSimulating" class="w-5 h-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="iconos.pauseFill"></svg>
        <svg v-else class="w-5 h-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="iconos.playFill"></svg>
      </button>
    </div>

    <div v-if="routeInfo" class="route-info">
      <div class="info-item">
        <span class="info-label">Distancia:</span>
        <span class="info-value">{{ routeInfo.distance }} km</span>
      </div>
      <div class="info-item">
        <span class="info-label">Duración:</span>
        <span class="info-value">{{ routeInfo.duration }}</span>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="geolocationError" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6" role="dialog" aria-modal="true" aria-label="Aviso">
            <button type="button" @click="geolocationError = ''" class="absolute top-4 right-4 text-neutral-slate hover:text-neutral-dark" aria-label="Cerrar">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <h3 class="text-lg font-bold text-neutral-dark mb-2">Aviso</h3>
            <p class="text-sm text-neutral-slate" style="white-space: pre-line;">{{ geolocationError }}</p>

            <div class="mt-6 flex justify-end">
              <button type="button" @click="geolocationError = ''" class="px-4 py-2 rounded-lg bg-lanzarote-blue text-white font-semibold hover:bg-lanzarote-yellow hover:text-black transition-all">
                Aceptar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>


<script setup>
import { ref, onMounted, watch, nextTick, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'
import 'leaflet-routing-machine'
import 'leaflet-control-geocoder/dist/Control.Geocoder.css'
import 'leaflet-control-geocoder'
import '../../css/taximap.css'

import svgGeoAlt from 'bootstrap-icons/icons/geo-alt.svg?raw'
import svgPlayFill from 'bootstrap-icons/icons/play-fill.svg?raw'
import svgPauseFill from 'bootstrap-icons/icons/pause-fill.svg?raw'
import svgPerson from 'bootstrap-icons/icons/person.svg?raw'
import svgGeoAltFill from 'bootstrap-icons/icons/geo-alt-fill.svg?raw'
import svgFlagFill from 'bootstrap-icons/icons/flag-fill.svg?raw'
import svgTaxiFront from 'bootstrap-icons/icons/taxi-front.svg?raw'

/**
 * Componente de mapa (Leaflet) para selección/visualización de ruta.
 *
 * Funcionalidades:
 * - Obtiene ubicación del usuario (si hay permisos) y la marca en el mapa.
 * - Muestra marcador de origen (pickup) y destino (dropoff) si están definidos.
 * - Calcula una ruta usando OSRM vía `leaflet-routing-machine`.
 * - Emite la distancia calculada para que el formulario pueda estimar precio.
 * - Incluye un modo de simulación que anima un marcador de taxi sobre la ruta.
 */

// Normaliza la URL base del servicio OSRM para que siempre termine en '/'.
const normalizeOsrmServiceUrl = (url) => {
  if (typeof url !== 'string') return null
  const trimmed = url.trim()
  if (!trimmed) return null

  return trimmed.endsWith('/') ? trimmed : `${trimmed}/`
}

const OSRM_SERVICE_URL = normalizeOsrmServiceUrl(import.meta.env.VITE_OSRM_SERVICE_URL)
  || '/api/osrm/route/v1/'

// Coordenadas iniciales de ejemplo (Lanzarote). Dropoff es opcional.
const props = defineProps({
  pickupLat: { type: Number, default: 28.963 },
  pickupLng: { type: Number, default: -13.550 },
  dropoffLat: { type: Number, default: null },
  dropoffLng: { type: Number, default: null }
})

// Eventos hacia el padre:
// - `distance-calculated`: distancia (km) cuando OSRM resuelve ruta
// - `location-found`: ubicación del usuario tras geolocalización
const emit = defineEmits(['distance-calculated', 'location-found'])

const mapElement = ref(null)

let map = null
let userMarker = null
let pickupMarker = null
let dropoffMarker = null
let routingControl = null
let taxiMarker = null
let simulationInterval = null
let currentRoutePoints = []

const isSimulating = ref(false)
const routeInfo = ref(null)
const userLocation = ref(null)
const geolocationError = ref('')

// Utilidad: bootstrap-icons entrega SVG completo; aquí extraemos el contenido interno.
const innerSvg = (raw) => raw
  .replace(/^<svg[^>]*>/i, '')
  .replace(/<\/svg>\s*$/i, '')
  .trim()

const iconos = {
  geoAlt: innerSvg(svgGeoAlt),
  playFill: innerSvg(svgPlayFill),
  pauseFill: innerSvg(svgPauseFill),
  person: innerSvg(svgPerson),
  geoAltFill: innerSvg(svgGeoAltFill),
  flagFill: innerSvg(svgFlagFill),
  taxiFront: innerSvg(svgTaxiFront),
}

const createSvgIcon = (svgInnerHtml) => {
  const html = `
    <svg viewBox="0 0 16 16" width="24" height="24" fill="currentColor" style="filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));">
      ${svgInnerHtml}
    </svg>
  `.trim()

  return L.divIcon({
    html,
    className: 'custom-marker',
    iconSize: [24, 24],
    popupAnchor: [0, -12]
  })
}

const icons = {
  user: createSvgIcon(iconos.person),
  pickup: createSvgIcon(iconos.geoAltFill),
  dropoff: createSvgIcon(iconos.flagFill),
  taxi: createSvgIcon(iconos.taxiFront)
}

const initMap = async () => {
  if (!mapElement.value || map) return

  await nextTick()

  map = L.map(mapElement.value).setView([28.963, -13.550], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  L.Control.geocoder({
    defaultMarkGeocode: false,
    placeholder: 'Buscar dirección...',
    errorMessage: 'No se encontró la dirección',
    geocoder: L.Control.Geocoder.nominatim({
      // Proxy backend para evitar CORS y centralizar límites/uso de Nominatim.
      // El geocoder añade el término de búsqueda al final de `q=`.
      serviceUrl: '/api/geocoding/search?format=json&addressdetails=1&limit=5&countrycodes=es&bounded=1&viewbox=-13.95,29.35,-13.20,28.85&q='
    })
  }).on('markgeocode', function(e) {
    // Búsqueda de direcciones: centra el mapa y deja un marcador informativo.
    const { center, name } = e.geocode
    map.setView(center, 15)
    L.marker(center, { icon: icons.pickup }).addTo(map)
      .bindPopup(name)
      .openPopup()
  }).addTo(map)

  getUserLocation()
}

const getUserLocation = () => {
  // Obtiene la ubicación del usuario. Si falla, se muestra un modal con explicación.
  if (!navigator.geolocation) {
    console.error('Geolocalización no soportada')

    return
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords
      userLocation.value = { lat: latitude, lng: longitude }
      
      if (userMarker) {
        userMarker.setLatLng([latitude, longitude])
      } else {
        userMarker = L.marker([latitude, longitude], { 
          icon: icons.user,
          zIndexOffset: 1000
        }).addTo(map)
          .bindPopup('Tu ubicación actual')
          .openPopup()
      }

      map.setView([latitude, longitude], 15)

      emit('location-found', { lat: latitude, lng: longitude })
    },
    (error) => {
      console.error('Error obteniendo ubicación:', error)
      let mensaje = 'No se pudo obtener tu ubicación.';
      if (error.code === 1) {
        mensaje += '\nPermiso denegado.\n\nPara restablecer el permiso en Chrome: \n- Haz clic en el icono de candado o ajustes junto a la URL.\n- Busca "Ubicación" y selecciona "Permitir".\n- Recarga la página.\n\nSi el prompt sigue sin aparecer, ve a Configuración > Privacidad y seguridad > Configuración de sitios > Ubicación y elimina la restricción para este sitio.';
      } else if (error.code === 2) {
        mensaje += ' Ubicación no disponible.';
      } else if (error.code === 3) {
        mensaje += ' Tiempo de espera agotado.';
      }
      mensaje += '\nActiva los permisos de ubicación o accede por HTTPS/localhost.';
      geolocationError.value = mensaje;
      userMarker = L.marker([28.963, -13.550], { icon: icons.user }).addTo(map)
        .bindPopup('Ubicación por defecto')
    }
  )
}

const centerOnUser = () => {
  if (userLocation.value) {
    map.setView([userLocation.value.lat, userLocation.value.lng], 15)
  } else {
    getUserLocation()
  }
}

const calculateRoute = () => {
  // Dibuja/actualiza marcadores y calcula la ruta cuando hay pickup+dropoff.
  if (!map) return

  if (routingControl) {
    map.removeControl(routingControl)
    routingControl = null
  }

  const hasPickup = Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)
  if (!hasPickup) return

  const pickup = [props.pickupLat, props.pickupLng]

  const hasDropoff = Number.isFinite(props.dropoffLat) && Number.isFinite(props.dropoffLng)

  if (pickupMarker) {
    pickupMarker.setLatLng(pickup)
  } else {
    pickupMarker = L.marker(pickup, { icon: icons.pickup }).addTo(map)
      .bindPopup('Origen')
  }

  if (!hasDropoff) {
    if (dropoffMarker) {
      map.removeLayer(dropoffMarker)
      dropoffMarker = null
    }

    routeInfo.value = null
    currentRoutePoints = []
    emit('distance-calculated', 0)

    return
  }

  const dropoff = [props.dropoffLat, props.dropoffLng]

  if (dropoffMarker) {
    dropoffMarker.setLatLng(dropoff)
  } else {
    dropoffMarker = L.marker(dropoff, { icon: icons.dropoff }).addTo(map)
      .bindPopup('Destino')
  }

  routingControl = L.Routing.control({
    waypoints: [
      L.latLng(pickup[0], pickup[1]),
      L.latLng(dropoff[0], dropoff[1])
    ],
    router: L.Routing.osrmv1({
      serviceUrl: OSRM_SERVICE_URL,
      profile: 'car'
    }),
    lineOptions: {
      styles: [{ color: '#6366f1', weight: 5 }],
      addWaypoints: false
    },
    showAlternatives: false,
    show: false,
    createMarker: () => null
  }).addTo(map)

  routingControl.on('routesfound', function(e) {
    // Evento cuando OSRM devuelve una ruta.
    const route = e?.routes?.[0]
    if (!route) return

    const summary = route.summary
    const coordinates = Array.isArray(route.coordinates) ? route.coordinates : []
    
    currentRoutePoints = coordinates
      .filter(coord => coord && Number.isFinite(coord.lat) && Number.isFinite(coord.lng))
      .map(coord => ({ lat: coord.lat, lng: coord.lng }))

    const totalDistance = Number(summary?.totalDistance)
    const totalTime = Number(summary?.totalTime)

    const distance = Number.isFinite(totalDistance) ? (totalDistance / 1000).toFixed(2) : null
    const duration = Number.isFinite(totalTime) ? Math.round(totalTime / 60) : null
    
    routeInfo.value = distance && duration !== null
      ? { distance, duration: `${duration} min` }
      : null

    if (distance) emit('distance-calculated', distance)

    const bounds = route.bounds

    if (bounds && typeof bounds.isValid === 'function' && bounds.isValid()) {
      map.fitBounds(bounds, { padding: [50, 50] })
      
      return
    }

    if (coordinates.length) {
      const computedBounds = L.latLngBounds(coordinates)
      if (computedBounds.isValid()) {
        map.fitBounds(computedBounds, { padding: [50, 50] })
      }
    }
  })

  routingControl.on('routingerror', function(err) {
    console.error('Routing error:', err)
    routeInfo.value = null
    currentRoutePoints = []
  })
}

const simulateTaxiMovement = () => {
  // Modo demo: recorre los puntos de la polilínea y mueve el icono de taxi.
  if (!currentRoutePoints.length || isSimulating.value) return

  isSimulating.value = true
  let currentPoint = 0

  if (!taxiMarker) {
    taxiMarker = L.marker(currentRoutePoints[0], { 
      icon: icons.taxi,
      zIndexOffset: 2000
    }).addTo(map)
  }

  simulationInterval = setInterval(() => {
    if (currentPoint < currentRoutePoints.length - 1) {
      currentPoint++
      const point = currentRoutePoints[currentPoint]
      taxiMarker.setLatLng([point.lat, point.lng])
      
      taxiMarker.setIcon(icons.taxi)
      
      if (currentPoint % 10 === 0) {
        map.setView([point.lat, point.lng], map.getZoom())
      }
    } else {
      stopSimulation()
      taxiMarker.bindPopup('Taxi ha llegado al destino').openPopup()
    }
  }, 100)
}

const stopSimulation = () => {
  // Limpia el intervalo para evitar leaks cuando se desmonta el componente.
  if (simulationInterval) {
    clearInterval(simulationInterval)
    simulationInterval = null
  }
  isSimulating.value = false
}

const toggleSimulation = () => {
  if (isSimulating.value) {
    stopSimulation()
  } else {
    simulateTaxiMovement()
  }
}

watch(
  () => [props.pickupLat, props.pickupLng, props.dropoffLat, props.dropoffLng],
  () => {
    // Recalcula ruta cuando cambian coordenadas.
    if (Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)) {
      calculateRoute()
    }
  },
  { deep: true }
)

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  stopSimulation()
  if (map) {
    map.remove()
  }
})
</script>
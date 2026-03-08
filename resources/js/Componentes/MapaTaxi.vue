<template>
  <div class="map-container">
    <div ref="mapElement" class="map"></div>
    
    <!-- Controles de mapa -->
    <div class="map-controls">
      <button 
        @click="centerOnUser" 
        class="control-btn"
        title="Centrar en mi ubicación"
      >
        <span class="text-lg">📍</span>
      </button>
      <button 
        @click="toggleSimulation" 
        class="control-btn"
        :class="{ 'active': isSimulating }"
        :title="isSimulating ? 'Detener simulación' : 'Simular viaje'"
      >
        <span class="text-lg">{{ isSimulating ? '⏸️' : '🚕' }}</span>
      </button>
    </div>

    <!-- Información de ruta -->
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

const normalizeOsrmServiceUrl = (url) => {
  if (typeof url !== 'string') return null
  const trimmed = url.trim()
  if (!trimmed) return null
  return trimmed.endsWith('/') ? trimmed : `${trimmed}/`
}

const OSRM_SERVICE_URL = normalizeOsrmServiceUrl(import.meta.env.VITE_OSRM_SERVICE_URL)
  || 'https://router.project-osrm.org/route/v1/'

// Props
const props = defineProps({
  pickupLat: { type: Number, default: 28.963 },
  pickupLng: { type: Number, default: -13.550 },
  dropoffLat: { type: Number, default: null },
  dropoffLng: { type: Number, default: null }
})

// Emits
const emit = defineEmits(['distance-calculated', 'location-found'])

// Referencias
const mapElement = ref(null)

// Estado del mapa
let map = null
let userMarker = null
let pickupMarker = null
let dropoffMarker = null
let routingControl = null
let taxiMarker = null
let simulationInterval = null
let currentRoutePoints = []

// Estado reactivo
const isSimulating = ref(false)
const routeInfo = ref(null)
const userLocation = ref(null)

// Configuración de iconos personalizados para Leaflet
const createIcon = (emoji, color = 'blue') => {
  return L.divIcon({
    html: `<div style="font-size: 24px; filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));">${emoji}</div>`,
    className: 'custom-marker',
    iconSize: [24, 24],
    popupAnchor: [0, -12]
  })
}

const icons = {
  user: createIcon('👤', 'green'),
  pickup: createIcon('📍', 'blue'),
  dropoff: createIcon('🏁', 'red'),
  taxi: createIcon('🚕', 'yellow')
}

// Inicializar mapa
const initMap = async () => {
  if (!mapElement.value || map) return

  await nextTick()

  // Configurar mapa con OpenStreetMap
  map = L.map(mapElement.value).setView([28.963, -13.550], 13)

  // Añadir capas de OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  // Añadir control de búsqueda
  L.Control.geocoder({
    defaultMarkGeocode: false,
    placeholder: 'Buscar dirección...',
    errorMessage: 'No se encontró la dirección',
    geocoder: L.Control.Geocoder.nominatim({
      serviceUrl: 'https://nominatim.openstreetmap.org/search?format=json&q='
    })
  }).on('markgeocode', function(e) {
    const { center, name } = e.geocode
    map.setView(center, 15)
    L.marker(center, { icon: icons.pickup }).addTo(map)
      .bindPopup(name)
      .openPopup()
  }).addTo(map)

  // Obtener ubicación del usuario al iniciar
  getUserLocation()
}

// Obtener ubicación del usuario
const getUserLocation = () => {
  if (!navigator.geolocation) {
    console.error('Geolocalización no soportada')
    return
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords
      userLocation.value = { lat: latitude, lng: longitude }
      
      // Actualizar marcador de usuario
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

      // Centrar mapa en usuario
      map.setView([latitude, longitude], 15)

      // Emitir ubicación encontrada
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
      alert(mensaje);
      // Si no se puede obtener la ubicación, usar ubicación por defecto
      userMarker = L.marker([28.963, -13.550], { icon: icons.user }).addTo(map)
        .bindPopup('Ubicación por defecto')
    }
  )
}

// Centrar mapa en usuario
const centerOnUser = () => {
  if (userLocation.value) {
    map.setView([userLocation.value.lat, userLocation.value.lng], 15)
  } else {
    getUserLocation()
  }
}

// Calcular ruta
const calculateRoute = () => {
  if (!map) return

  // Eliminar ruta anterior
  if (routingControl) {
    map.removeControl(routingControl)
    routingControl = null
  }

  // Validar puntos
  const hasPickup = Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)
  if (!hasPickup) return

  const pickup = [props.pickupLat, props.pickupLng]

  const hasDropoff = Number.isFinite(props.dropoffLat) && Number.isFinite(props.dropoffLng)

  // Actualizar marcadores
  if (pickupMarker) {
    pickupMarker.setLatLng(pickup)
  } else {
    pickupMarker = L.marker(pickup, { icon: icons.pickup }).addTo(map)
      .bindPopup('Origen')
  }

  // Si todavía no hay destino, NO inventar uno. Limpiar estado y salir.
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

  // Crear nueva ruta con OSRM
  routingControl = L.Routing.control({
    waypoints: [
      L.latLng(pickup[0], pickup[1]),
      L.latLng(dropoff[0], dropoff[1])
    ],
    router: L.Routing.osrmv1({
      serviceUrl: OSRM_SERVICE_URL,
      profile: 'car' // Para rutas en coche/taxi
    }),
    lineOptions: {
      styles: [{ color: '#6366f1', weight: 5 }],
      addWaypoints: false
    },
    showAlternatives: false,
    show: false, // No mostrar instrucciones paso a paso
    createMarker: () => null // No crear marcadores adicionales
  }).addTo(map)

  // Escuchar evento de ruta encontrada
  routingControl.on('routesfound', function(e) {
    const route = e?.routes?.[0]
    if (!route) return

    const summary = route.summary
    const coordinates = Array.isArray(route.coordinates) ? route.coordinates : []
    
    // Guardar puntos de la ruta para simulación
    currentRoutePoints = coordinates
      .filter(coord => coord && Number.isFinite(coord.lat) && Number.isFinite(coord.lng))
      .map(coord => ({ lat: coord.lat, lng: coord.lng }))

    // Calcular distancia y duración
    const totalDistance = Number(summary?.totalDistance)
    const totalTime = Number(summary?.totalTime)

    const distance = Number.isFinite(totalDistance) ? (totalDistance / 1000).toFixed(2) : null
    const duration = Number.isFinite(totalTime) ? Math.round(totalTime / 60) : null
    
    routeInfo.value = distance && duration !== null
      ? { distance, duration: `${duration} min` }
      : null

    // Emitir distancia calculada
    if (distance) emit('distance-calculated', distance)

    // Ajustar vista del mapa para mostrar toda la ruta
    const bounds = route.bounds

    // Ajustar vista del mapa para mostrar toda la ruta (solo si hay bounds válidos)
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

// Simulación de movimiento del taxi
const simulateTaxiMovement = () => {
  if (!currentRoutePoints.length || isSimulating.value) return

  isSimulating.value = true
  let currentPoint = 0

  // Crear marcador del taxi si no existe
  if (!taxiMarker) {
    taxiMarker = L.marker(currentRoutePoints[0], { 
      icon: icons.taxi,
      zIndexOffset: 2000
    }).addTo(map)
  }

  // Animar movimiento
  simulationInterval = setInterval(() => {
    if (currentPoint < currentRoutePoints.length - 1) {
      currentPoint++
      const point = currentRoutePoints[currentPoint]
      taxiMarker.setLatLng([point.lat, point.lng])
      
      // Rotar el marcador (simulado con emoji)
      taxiMarker.setIcon(createIcon('🚕'))
      
      // Centrar mapa en el taxi cada 10 puntos
      if (currentPoint % 10 === 0) {
        map.setView([point.lat, point.lng], map.getZoom())
      }
    } else {
      // Llegó al destino
      stopSimulation()
      taxiMarker.bindPopup('🚕 Taxi ha llegado al destino').openPopup()
    }
  }, 100) // Actualizar cada 100ms para movimiento suave
}

// Detener simulación
const stopSimulation = () => {
  if (simulationInterval) {
    clearInterval(simulationInterval)
    simulationInterval = null
  }
  isSimulating.value = false
}

// Alternar simulación
const toggleSimulation = () => {
  if (isSimulating.value) {
    stopSimulation()
  } else {
    simulateTaxiMovement()
  }
}

// Watchers
watch(
  () => [props.pickupLat, props.pickupLng, props.dropoffLat, props.dropoffLng],
  () => {
    if (Number.isFinite(props.pickupLat) && Number.isFinite(props.pickupLng)) {
      calculateRoute()
    }
  },
  { deep: true }
)

// Lifecycle hooks
onMounted(() => {
  initMap()
})

// Cleanup
onUnmounted(() => {
  stopSimulation()
  if (map) {
    map.remove()
  }
})
</script>




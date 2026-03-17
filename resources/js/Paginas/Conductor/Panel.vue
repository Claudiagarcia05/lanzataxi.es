<template>
  <DisposicionConductor>
    <div class="max-w-7xl mx-auto">
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">Panel del taxista</h1>
        <p class="text-blue-100">Gestiona solicitudes, viajes y tu vehículo</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div v-for="stat in estadisticas" :key="stat.label" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-2">
            <svg v-if="stat.iconType === 'path'" class="w-7 h-7 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath(stat.icon)" />
            </svg>
            <svg v-else class="w-7 h-7 text-neutral-dark" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon(stat.icon)"></svg>
          </div>
          <p class="text-2xl font-bold text-neutral-dark">{{ stat.value }}</p>
          <p class="text-sm text-neutral-slate">{{ stat.label }}</p>
        </div>
      </div>

    <div v-if="!estaOcupado && solicitudesPendientes.length > 0" class="fixed top-24 right-6 w-96 max-h-[calc(100vh-7rem)] overflow-auto bg-white rounded-2xl shadow-sm border border-neutral-volcanic z-40">
      <div class="p-4 border-b border-neutral-volcanic flex items-center justify-between">
        <h3 class="font-semibold text-neutral-dark">
          <svg class="inline-block w-5 h-5 mr-2" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('fileText')"></svg>
          Solicitudes pendientes ({{ solicitudesPendientes.length }})
        </h3>
      </div>

      <div v-if="viajeStore.error" class="p-4 border-b border-neutral-volcanic">
        <p class="text-sm text-red-600">{{ viajeStore.error }}</p>
      </div>

      <div class="p-4 space-y-3">
        <div v-for="solicitud in solicitudesPendientes" :key="solicitud.id" class="bg-neutral-soft rounded-xl p-4 border border-neutral-volcanic">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-neutral-dark truncate">{{ solicitud.pasajeroName }}</p>
              <p class="text-xs text-neutral-slate mt-1">
                <span class="font-medium text-neutral-dark">Origen:</span> {{ solicitud.pickup }}
              </p>
              <p class="text-xs text-neutral-slate">
                <span class="font-medium text-neutral-dark">Destino:</span> {{ solicitud.dropoff }}
              </p>

              <div class="flex flex-wrap gap-2 mt-2 text-xs">
                <span class="bg-white px-2 py-1 rounded-full border border-neutral-volcanic">
                  <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('rulers')"></svg>
                  {{ solicitud.distance }} km
                </span>
                <span class="bg-white px-2 py-1 rounded-full border border-neutral-volcanic">
                  <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('money')" />
                  </svg>
                  {{ solicitud.price }} €
                </span>
              </div>
            </div>

            <div class="flex flex-col gap-2 shrink-0">
              <button @click="aceptarViaje(solicitud.id)" class="bg-green-500 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-600 transition-colors">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('checkLg')"></svg>
                Aceptar
              </button>
              <button @click="viajeStore.descartarOferta(solicitud.id)" class="bg-red-500 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-600 transition-colors">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('xLg')"></svg>
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="viajesAceptados.length > 0" class="mb-8">
      <h3 class="font-semibold text-neutral-dark mb-4 text-lg">
        <svg class="inline-block w-5 h-5 mr-2" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('taxiFront')"></svg>
        Viaje en curso
      </h3>
      <div v-for="viaje in viajesAceptados" :key="viaje.id" class="bg-lanzarote-yellow/20 border border-lanzarote-yellow rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h4 class="font-semibold text-neutral-dark">
              <svg class="inline-block w-5 h-5 mr-2" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('personCircle')"></svg>
              Pasajero: {{ viaje.pasajeroName }}
            </h4>
            <p class="text-sm text-neutral-slate">Estado: {{ viaje.estado === 'accepted' ? 'Aceptado' : 'En curso' }}</p>
          </div>
          <span class="bg-lanzarote-blue text-white px-3 py-1 rounded-full text-sm">
            <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('money')" />
            </svg>
            {{ viaje.price }} €
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div class="bg-white/50 p-3 rounded-lg">
            <p class="text-xs text-neutral-slate">
              <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('geoAltFill')"></svg>
              Origen
            </p>
            <p class="font-medium">{{ viaje.pickup }}</p>
          </div>
          <div class="bg-white/50 p-3 rounded-lg">
            <p class="text-xs text-neutral-slate">
              <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('flagFill')"></svg>
              Destino
            </p>
            <p class="font-medium">{{ viaje.dropoff }}</p>
          </div>
        </div>

        <div class="flex space-x-3">
          <button v-if="viaje.estado === 'accepted'" @click="iniciarViaje(viaje.id)" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
            <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('playFill')"></svg>
            Iniciar viaje
          </button>
          <button v-if="['accepted','in_progress'].includes(viaje.estado)" @click="abrirMapa(viaje)" class="bg-white text-neutral-dark px-4 py-2 rounded-lg text-sm border border-neutral-volcanic hover:bg-neutral-soft">
            Ver mapa
          </button>
          <button v-if="viaje.estado === 'in_progress'" @click="completarViaje(viaje.id)" class="bg-lanzarote-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black">
            <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('check2Circle')"></svg>
            Completar viaje
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Mapa (conductor) -->
    <div v-if="modalMapaAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div class="w-full max-w-5xl bg-white rounded-xl shadow-lg border border-neutral-volcanic">
        <div class="p-5 border-b border-neutral-volcanic flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-neutral-dark">Mapa del viaje</h3>
            <p class="text-sm text-neutral-slate">Origen y destino del viaje</p>
          </div>
          <button @click="cerrarMapa" class="p-2 rounded-lg hover:bg-neutral-soft">
            <span class="text-neutral-slate font-semibold text-lg leading-none">X</span>
          </button>
        </div>

        <div class="p-5">
          <div class="h-[60vh] min-h-[360px]">
            <MapaSeguimiento
              v-if="viajeMapa"
              :pickupLat="viajeMapa.pickupLat"
              :pickupLng="viajeMapa.pickupLng"
              :dropoffLat="viajeMapa.dropoffLat"
              :dropoffLng="viajeMapa.dropoffLng"
              :taxiLat="conductorStore.ubicacionActual?.lat ?? null"
              :taxiLng="conductorStore.ubicacionActual?.lng ?? null"
              :estado="viajeMapa.estado"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
      <div class="p-6 border-b border-neutral-volcanic flex items-center justify-between gap-4">
        <h3 class="font-semibold text-neutral-dark">
          Viajes de hoy
        </h3>

        <span class="px-3 py-1 rounded-full text-sm font-medium select-none" :class="estaOcupado ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" aria-live="polite">
          {{ estaOcupado ? 'Ocupado' : 'Libre' }}
        </span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-neutral-soft">
            <tr>
              <th class="text-left p-4 text-sm font-medium text-neutral-slate">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('clock')"></svg>
                Hora
              </th>
              <th class="text-left p-4 text-sm font-medium text-neutral-slate">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('person')"></svg>
                Pasajero
              </th>
              <th class="text-left p-4 text-sm font-medium text-neutral-slate">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('geoAlt')"></svg>
                Origen
              </th>
              <th class="text-left p-4 text-sm font-medium text-neutral-slate">
                <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('flag')"></svg>
                Destino
              </th>
              <th class="text-left p-4 text-sm font-medium text-neutral-slate">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('money')" />
                </svg>
                Ganancia
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="viaje in viajesHoy" :key="viaje.id" class="border-b border-neutral-volcanic hover:bg-neutral-soft">
              <td class="p-4 font-medium">{{ new Date(viaje.date).toLocaleTimeString() }}</td>
              <td class="p-4">{{ viaje.pasajeroName }}</td>
              <td class="p-4 text-neutral-slate">{{ viaje.pickup }}</td>
              <td class="p-4 text-neutral-slate">{{ viaje.dropoff }}</td>
              <td class="p-4 font-medium text-green-600">{{ viaje.price }} €</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="conductorStore.perfil" class="mt-8 bg-white rounded-xl shadow-sm p-6">
      <h3 class="font-semibold text-neutral-dark mb-4">Mi vehículo</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div>
          <p class="text-xs text-neutral-slate">
            <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('carFront')"></svg>
            Modelo
          </p>
          <p class="font-medium">{{ conductorStore.perfil.vehiculo.modelo }}</p>
        </div>
        <div>
          <p class="text-xs text-neutral-slate">
            <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('upcScan')"></svg>
            Matrícula
          </p>
          <p class="font-medium">{{ conductorStore.perfil.vehiculo.matricula }}</p>
        </div>
        <div>
          <p class="text-xs text-neutral-slate">
            <svg class="inline-block w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" v-html="icon('people')"></svg>
            Capacidad
          </p>
          <p class="font-medium">{{ conductorStore.perfil.vehiculo.capacidad ? `${conductorStore.perfil.vehiculo.capacidad} personas` : '' }}</p>
        </div>
      </div>
    </div>
    </div>
  </DisposicionConductor>
</template>


<script setup>
import { computed, ref } from 'vue'
import DisposicionConductor from '../../Disposiciones/DisposicionConductor.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import { useConductorStore } from '../../Almacenes/almacenConductor.js'
import MapaSeguimiento from '../../Componentes/MapaSeguimiento.vue'

import svgCarFront from 'bootstrap-icons/icons/car-front.svg?raw'
import svgFileText from 'bootstrap-icons/icons/file-text.svg?raw'
import svgGeoAltFill from 'bootstrap-icons/icons/geo-alt-fill.svg?raw'
import svgFlagFill from 'bootstrap-icons/icons/flag-fill.svg?raw'
import svgRulers from 'bootstrap-icons/icons/rulers.svg?raw'
import svgCheckLg from 'bootstrap-icons/icons/check-lg.svg?raw'
import svgXLg from 'bootstrap-icons/icons/x-lg.svg?raw'
import svgTaxiFront from 'bootstrap-icons/icons/taxi-front.svg?raw'
import svgPersonCircle from 'bootstrap-icons/icons/person-circle.svg?raw'
import svgPlayFill from 'bootstrap-icons/icons/play-fill.svg?raw'
import svgCheck2Circle from 'bootstrap-icons/icons/check2-circle.svg?raw'
import svgClock from 'bootstrap-icons/icons/clock.svg?raw'
import svgPerson from 'bootstrap-icons/icons/person.svg?raw'
import svgGeoAlt from 'bootstrap-icons/icons/geo-alt.svg?raw'
import svgFlag from 'bootstrap-icons/icons/flag.svg?raw'
import svgUpcScan from 'bootstrap-icons/icons/upc-scan.svg?raw'
import svgPeople from 'bootstrap-icons/icons/people.svg?raw'

const authStore = useAuthStore()
const viajeStore = useViajeStore()
const conductorStore = useConductorStore()

const modalMapaAbierto = ref(false)
const viajeMapa = ref(null)

const iconPaths = {
  viajes: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  money: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}

const iconPath = (name) => iconPaths[name] || ''

const innerSvg = (raw) => raw
  .replace(/^<svg[^>]*>/i, '')
  .replace(/<\/svg>\s*$/i, '')
  .trim()

const iconos = {
  carFront: innerSvg(svgCarFront),
  fileText: innerSvg(svgFileText),
  geoAltFill: innerSvg(svgGeoAltFill),
  flagFill: innerSvg(svgFlagFill),
  rulers: innerSvg(svgRulers),
  checkLg: innerSvg(svgCheckLg),
  xLg: innerSvg(svgXLg),
  taxiFront: innerSvg(svgTaxiFront),
  personCircle: innerSvg(svgPersonCircle),
  playFill: innerSvg(svgPlayFill),
  check2Circle: innerSvg(svgCheck2Circle),
  clock: innerSvg(svgClock),
  person: innerSvg(svgPerson),
  geoAlt: innerSvg(svgGeoAlt),
  flag: innerSvg(svgFlag),
  upcScan: innerSvg(svgUpcScan),
  people: innerSvg(svgPeople),
}

const icon = (name) => iconos[name] || ''

const estadisticas = computed(() => {
  const today = new Date().toDateString()
  const viajesHoy = viajeStore.viajesConductor
    .filter(t => new Date(t.date).toDateString() === today)

  const earnings = viajesHoy.reduce((sum, t) => sum + (t.price || 0), 0)

  return [
    { label: 'Viajes hoy', value: viajesHoy.length, iconType: 'path', icon: 'calendar' },
    { label: 'Ganancias hoy', value: `${earnings.toFixed(2)} €`, iconType: 'path', icon: 'money' }
  ]
})

const solicitudesPendientes = computed(() => {
  
  return viajeStore.viajesPendientes.filter(t => !t.conductorId)
})

const viajesAceptados = computed(() => {

  return viajeStore.viajesConductor.filter(t => ['accepted', 'in_progress'].includes(t.estado))
})

const estaOcupado = computed(() => viajesAceptados.value.length > 0)

const viajesHoy = computed(() => {

  const today = new Date().toDateString()
  
  return viajeStore.viajesConductor
    .filter(t => new Date(t.date).toDateString() === today && ['accepted', 'in_progress', 'completed'].includes(t.estado))
    .sort((a, b) => new Date(b.date) - new Date(a.date))
})

const aceptarViaje = async (viajeId) => {
  await conductorStore.aceptarViaje(viajeId)
}

const iniciarViaje = async (viajeId) => {
  await viajeStore.iniciarViaje(viajeId)
}

const completarViaje = async (viajeId) => {
  await viajeStore.completarViaje(viajeId)
  await conductorStore.actualizarEstadisticas()
}

const abrirMapa = (viaje) => {
  viajeMapa.value = viaje || null
  modalMapaAbierto.value = true
}

const cerrarMapa = () => {
  modalMapaAbierto.value = false
  viajeMapa.value = null
}
</script>
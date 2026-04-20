<template>
  <DisposicionConductor>
    <div class="max-w-7xl mx-auto">
      <!--
        Panel de ganancias del conductor.
        - Resume ingresos por semana/mes/total.
        - Dibuja un gráfico simple (SVG) agregando importes por día.
        - Permite exportar un informe en PDF con métricas clave.

        Nota: este componente hace validaciones y cálculos para UX, pero el backend debe
        ser la fuente de verdad de importes/estados de pago.
      -->
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">Mis Ganancias</h1>
        <p class="text-blue-100">Controla tus ingresos y estadísticas como taxista</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-neutral-slate text-sm">Esta semana</span>
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('calendar')" />
            </svg>
          </div>
          <p class="text-3xl font-bold text-neutral-dark">{{ formatMoney(resumenSemana.total) }}</p>
          <p class="text-xs text-neutral-slate mt-2">{{ resumenSemana.viajes }} viajes</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-neutral-slate text-sm">Este mes</span>
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('trendUp')" />
            </svg>
          </div>
          <p class="text-3xl font-bold text-neutral-dark">{{ formatMoney(resumenMes.total) }}</p>
          <p class="text-xs text-neutral-slate mt-2">{{ resumenMes.viajes }} viajes</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-neutral-slate text-sm">Total histórico</span>
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('trophy')" />
            </svg>
          </div>
          <p class="text-3xl font-bold text-lanzarote-blue">{{ formatMoney(resumenTotal.total) }}</p>
          <p class="text-xs text-neutral-slate mt-2">{{ resumenTotal.viajes }} viajes completados</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-neutral-dark">Evolución de ingresos</h2>
            
            <select v-model="periodoGrafico" class="px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue bg-white text-sm">
              <option value="7d">Últimos 7 días</option>
              <option value="30d">Últimos 30 días</option>
              <option value="90d">Últimos 3 meses</option>
              <option value="year">Este año</option>
            </select>
          </div>

          <div class="h-64 bg-neutral-soft rounded-lg p-4">
            <svg viewBox="0 0 700 200" class="w-full h-full" role="img" aria-label="Gráfico de ingresos">
              <g>
                <line x1="0" y1="170" x2="700" y2="170" class="stroke-current text-neutral-volcanic" stroke-width="1" />
              </g>

              <g v-for="(p, idx) in puntosGrafico" :key="p.key">
                <rect :x="idx * anchoBarra + paddingBarra" :y="170 - p.alto" :width="anchoBarra - paddingBarra * 2" :height="p.alto" class="fill-current text-lanzarote-blue" rx="3"/>

                <text v-if="mostrarEtiqueta(idx)" :x="idx * anchoBarra + (anchoBarra / 2)" y="192" text-anchor="middle" class="fill-current text-neutral-slate" font-size="10">
                  {{ p.label }}
                </text>
              </g>

              <text v-if="puntosGrafico.length === 0" x="350" y="110" text-anchor="middle" class="fill-current text-neutral-slate" font-size="12">
                Sin datos
              </text>
            </svg>
          </div>

          <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="text-center">
              <p class="text-2xl font-bold text-neutral-dark">{{ metricasPeriodo.viajes }}</p>
              <p class="text-xs text-neutral-slate">Viajes</p>
            </div>
            <div class="text-center border-x border-neutral-volcanic">
              <p class="text-2xl font-bold text-neutral-dark">{{ formatMoney(metricasPeriodo.promedio) }}</p>
              <p class="text-xs text-neutral-slate">Promedio por viaje</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-neutral-dark">{{ metricasPeriodo.tasaAceptacion }}%</p>
              <p class="text-xs text-neutral-slate">Tasa de aceptación</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
            <h3 class="font-semibold text-neutral-dark mb-4">Métricas clave</h3>
            
            <div class="space-y-4">
              <div class="flex justify-between items-center py-2 border-b border-neutral-volcanic">
                <span class="text-neutral-slate">Horas conectado</span>
                <span class="font-semibold text-neutral-dark">{{ horasConectadoLabel }}</span>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-neutral-volcanic">
                <span class="text-neutral-slate">Kilómetros</span>
                <span class="font-semibold text-neutral-dark">{{ metricasMes.km.toFixed(1) }} km</span>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-neutral-volcanic">
                <span class="text-neutral-slate">Ingresos mensuales</span>
                <span class="font-semibold text-green-600">{{ formatMoney(resumenMes.total) }}</span>
              </div>
              
              <div class="flex justify-between items-center py-2">
                <span class="text-neutral-slate">Viajes completados</span>
                <span class="font-semibold text-lanzarote-blue">{{ viajesCompletadosMes.length }}</span>
              </div>
            </div>

            <div v-if="esUltimoDiaMes" class="mt-4 bg-neutral-soft border border-neutral-volcanic rounded-lg p-3 text-sm text-neutral-dark">
              Hoy es el último día del mes. Si quieres guardar tus Métricas clave en PDF, haz clic en “Exportar información”.
            </div>

            <button class="w-full mt-6 bg-lanzarote-blue text-white font-medium py-3 px-4 rounded-lg hover:bg-lanzarote-yellow hover:text-black transition-colors" @click="exportInforme">
              Exportar información
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-semibold text-neutral-dark">Ganancias recientes</h2>
          <span class="text-sm text-neutral-slate">Últimos {{ viajesRecientes.length }} viajes completados</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-soft rounded-lg">
              <tr>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Fecha</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Origen</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Destino</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Distancia</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Ganancia</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Método pago</th>
                <th class="text-left p-4 text-sm font-medium text-neutral-slate">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="viaje in viajesRecientes" :key="viaje.id" class="border-b border-neutral-volcanic hover:bg-neutral-soft/50">
                <td class="p-4 text-sm text-neutral-dark">{{ formatDate(viaje.created_at) }}</td>
                <td class="p-4 text-sm text-neutral-dark">{{ viaje.pickup_address || viaje.pickup }}</td>
                <td class="p-4 text-sm text-neutral-dark">{{ viaje.dropoff_address || viaje.dropoff }}</td>
                <td class="p-4 text-sm text-neutral-dark">{{ (viaje.distance || 0).toFixed(1) }} km</td>
                <td class="p-4 text-sm font-semibold text-lanzarote-blue">{{ formatMoney(viaje.price || 0) }}</td>
                <td class="p-4 text-sm text-neutral-slate">{{ metodoPagoLabel(viaje.pago_method) }}</td>
                <td class="p-4">
                  <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Completado</span>
                </td>
              </tr>

              <tr v-if="viajesRecientes.length === 0">
                <td colspan="7" class="p-6 text-center text-sm text-neutral-slate">Aún no tienes viajes completados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center gap-3 mb-3">
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('money')" />
            </svg>
            <h3 class="font-semibold text-neutral-dark">Efectivo</h3>
          </div>
            <p class="text-2xl font-bold text-neutral-dark">{{ formatMoney(resumenPago.cash.total) }}</p>
            <p class="text-sm text-neutral-slate mt-1">{{ resumenPago.cash.viajes }} viajes</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center gap-3 mb-3">
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('creditCard')" />
            </svg>
            <h3 class="font-semibold text-neutral-dark">Tarjeta</h3>
          </div>
            <p class="text-2xl font-bold text-neutral-dark">{{ formatMoney(resumenPago.card.total) }}</p>
            <p class="text-sm text-neutral-slate mt-1">{{ resumenPago.card.viajes }} viajes</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center gap-3 mb-3">
            <svg class="w-6 h-6 text-neutral-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath('deviceMobile')" />
            </svg>
            <h3 class="font-semibold text-neutral-dark">Cartera digital</h3>
          </div>
            <p class="text-2xl font-bold text-neutral-dark">{{ formatMoney(resumenPago.app.total) }}</p>
            <p class="text-sm text-neutral-slate mt-1">{{ resumenPago.app.viajes }} viajes</p>
        </div>
      </div>
    </div>
  </DisposicionConductor>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import DisposicionConductor from '../../Disposiciones/DisposicionConductor.vue'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import { useConductorStore } from '../../Almacenes/almacenConductor.js'
import { jsPDF } from 'jspdf'

const filtroPeriodo = ref('all')

const periodoGrafico = ref('7d')

const viajeStore = useViajeStore()
const conductorStore = useConductorStore()

const nowMs = ref(Date.now())
let tickIntervalId = null

// Clave estable para nombres de archivo (YYYY-MM).
const mesActualKey = () => {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')

  return `${y}-${m}`
}

// Convierte un File/Blob a DataURL (necesario para incrustar imágenes en jsPDF).
const fileToDataUrl = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader()
  reader.onload = () => resolve(reader.result)
  reader.onerror = () => reject(new Error('No se pudo leer el archivo'))
  reader.readAsDataURL(file)
})

// Carga un PNG público y devuelve DataURL + dimensiones reales (para escalar el logo).
const loadPublicImagePng = async (url) => {
  const res = await fetch(url, { cache: 'no-store' })
  if (!res.ok) return null
  const blob = await res.blob()
  const dataUrl = await fileToDataUrl(blob)

  const dims = await new Promise((resolve) => {
    const img = new Image()
    img.onload = () => resolve({ w: img.naturalWidth, h: img.naturalHeight })
    img.onerror = () => resolve(null)
    img.src = dataUrl
  })

  return { dataUrl, dims }
}

const exportInforme = async () => {
  // Exporta un PDF con:
  // - Datos personales/vehículo (desde el perfil del conductor)
  // - Métricas clave calculadas en el mes actual
  // Importante: el PDF se genera en cliente; si el backend cambia formatos, ajustar aquí.
  const now = new Date()
  const etiquetaMes = now.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
  const fechaLabel = now.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })

  // Intentamos refrescar el perfil para que el informe salga actualizado.
  // Si falla, seguimos con lo que haya en store (evita bloquear el PDF por un error puntual).
  await conductorStore.obtenerPerfilConductor().catch(() => {})

  const perfil = conductorStore.perfil
  const capacidad = perfil?.vehiculo?.capacidad
  const datosPersonales = [
    ['Nombre', perfil?.name || '—'],
    ['Email', perfil?.email || '—'],
    ['Teléfono', perfil?.phone || '—'],
    ['Vehículo', perfil?.vehiculo?.modelo || '—'],
    ['Matrícula', perfil?.vehiculo?.matricula || '—'],
    ['Capacidad', Number.isFinite(Number(capacidad)) ? `${Number(capacidad)} plazas` : '—'],
  ]

  const rows = [
    ['Horas conectado', horasConectadoLabel.value],
    ['Kilómetros', `${metricasMes.value.km.toFixed(1)} km`],
    ['Ingresos mensuales', formatMoney(resumenMes.value.total)],
    ['Viajes completados', String(viajesCompletadosMes.value.length)],
  ]

  const FAMILIA_FUENTE_PDF = 'helvetica'
  const doc = new jsPDF({ unit: 'pt', format: 'a4' })
  const marginX = 48
  const pageWidth = doc.internal.pageSize.getWidth()
  let y = 56

  // Logo de cabecera: se escala manteniendo proporciones para que no deforme.
  const logo = await loadPublicImagePng('/images/logo.png').catch(() => null)
  if (logo?.dataUrl) {
    const maxW = 140
    const maxH = 52

    let w = maxW
    let h = 40
    if (logo.dims?.w && logo.dims?.h) {
      const ratio = logo.dims.w / logo.dims.h
      w = Math.min(maxW, maxH * ratio)
      h = w / ratio
      if (h > maxH) {
        h = maxH
        w = h * ratio
      }
    }

    doc.addImage(logo.dataUrl, 'PNG', marginX, y - 8, w, h)
    y += h + 10
  }

  doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
  doc.setFontSize(11)
  doc.setTextColor(80)
  doc.text(`Informe mensual · ${etiquetaMes}`, marginX, y)
  doc.text(`Generado: ${fechaLabel}`, pageWidth - marginX, y, { align: 'right' })
  doc.setTextColor(0)
  y += 22

  doc.setDrawColor(220)
  doc.line(marginX, y, pageWidth - marginX, y)
  y += 22

  doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
  doc.setFontSize(13)
  doc.text('Datos personales', marginX, y)
  y += 16

  doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
  doc.setFontSize(11)
  const labelW = 110
  const valueW = pageWidth - marginX * 2 - labelW

  // Bloque de datos personales en formato etiqueta: valor.
  for (const [label, value] of datosPersonales) {
    doc.setTextColor(90)
    doc.text(`${label}:`, marginX, y)
    doc.setTextColor(0)
    doc.text(String(value), marginX + labelW, y, { maxWidth: valueW })
    y += 16
  }

  y += 6
  doc.setDrawColor(220)
  doc.line(marginX, y, pageWidth - marginX, y)
  y += 22

  doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
  doc.setFontSize(13)
  doc.text('Métricas clave', marginX, y)
  y += 18

  doc.setFontSize(12)
  const metricLabelWidth = 260
  const metricValueX = pageWidth - marginX
  // Filas: etiqueta alineada a la izquierda + valor alineado a la derecha.
  for (const [label, value] of rows) {
    doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
    doc.text(String(label), marginX, y, { maxWidth: metricLabelWidth })
    doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
    doc.text(String(value), metricValueX, y, { align: 'right' })
    y += 22
    doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
    doc.setDrawColor(235)
    doc.line(marginX, y, pageWidth - marginX, y)
    y += 14
  }

  const fileMonth = mesActualKey()
  doc.save(`metricas-clave-${fileMonth}.pdf`)
}

const iconPaths = {
  money: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  trendUp: 'M3 17l6-6 4 4 8-8M14 7h7v7',
  trophy: 'M8 21h8m-4-4v4m0-4a5 5 0 005-5V4H7v8a5 5 0 005 5zM5 4H4a1 1 0 00-1 1v2a4 4 0 004 4m12-7h1a1 1 0 011 1v2a4 4 0 01-4 4',
  star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.97a1 1 0 00.95.69h4.174c.969 0 1.371 1.24.588 1.81l-3.377 2.455a1 1 0 00-.364 1.118l1.287 3.97c.3.921-.755 1.688-1.538 1.118l-3.377-2.455a1 1 0 00-1.176 0l-3.377 2.455c-.783.57-1.838-.197-1.538-1.118l1.287-3.97a1 1 0 00-.364-1.118L2.25 9.397c-.783-.57-.38-1.81.588-1.81h4.174a1 1 0 00.95-.69l1.286-3.97z',
  creditCard: 'M3 10h18M7 15h2m4 0h2M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z',
  deviceMobile: 'M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2zM11 18h2',
}

const iconPath = (name) => iconPaths[name] || ''

const formatDate = (dateString) => {
  const date = new Date(dateString)

  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatMoney = (value) => {
  const number = Number(value || 0)

  return `${number.toFixed(2)} €`
}

const metodoPagoLabel = (metodo) => {
  const map = {
    cash: 'Efectivo',
    card: 'Tarjeta',
    app: 'Cartera',
  }

  return map[metodo] || '—'
}

const startOfWeek = (date) => {
  const d = new Date(date)
  const day = d.getDay() || 7
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - (day - 1))

  return d
}

const startOfMonth = (date) => {
  const d = new Date(date)

  return new Date(d.getFullYear(), d.getMonth(), 1, 0, 0, 0, 0)
}

const esUltimoDiaMes = computed(() => {
  const d = new Date()
  const ultimo = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()

  return d.getDate() === ultimo
})

const misViajesCompletados = computed(() => {
  // Viajes finalizados (completados) del conductor.
  return viajeStore.viajesConductor
    .filter(t => t.estado === 'completed')
})

const viajeTienePago = (t) => {
  // Un viaje cuenta como ingreso si:
  // - existe pago y está marcado como pagado, o
  // - está completado y el método es efectivo/tarjeta.
  // (La cartera/app puede tener otro flujo de confirmación.)
  if (t?.pago && t.pago.status === 'paid') return true

  return t?.estado === 'completed' && ['cash', 'card'].includes(t?.pago_method)
}

const viajesConIngreso = computed(() => {
  // Subconjunto de viajes que aportan ingresos.
  return viajeStore.viajesConductor.filter(viajeTienePago)
})

const resumenTotal = computed(() => {
  const viajes = viajesConIngreso.value
  // Se prioriza `pago.amount` si existe, y si no se usa `price`.
  // Esto ayuda a reflejar el importe realmente cobrado cuando el backend lo provee.
  const total = viajes.reduce((sum, t) => sum + (Number(t.pago?.amount ?? t.price) || 0), 0)

  return { viajes: viajes.length, total }
})

const segundosConectadoMes = computed(() => {
  // Tiempo conectado del mes.
  // - `tiempoConectadoMesSegundos` viene del backend/store como base acumulada.
  // - Si está en línea, sumamos el “extra” desde la última marca `estadoActualizadoEnMs`.
  const base = Number(conductorStore.tiempoConectadoMesSegundos || 0)
  if (!conductorStore.estaEnLinea) return base
  const last = Number(conductorStore.estadoActualizadoEnMs || 0)
  if (!last) return base
  const extra = Math.floor((nowMs.value - last) / 1000)

  return base + Math.max(0, extra)
})

const horasConectadoLabel = computed(() => {
  // Presentación amigable (h:mm) para el panel.
  const total = Math.max(0, Math.floor(Number(segundosConectadoMes.value || 0)))
  const h = Math.floor(total / 3600)
  const m = Math.floor((total % 3600) / 60)

  return `${h} h ${String(m).padStart(2, '0')} min`
})

const resumenSemana = computed(() => {
  const now = new Date()
  const from = startOfWeek(now)
  const viajes = viajesConIngreso.value.filter(t => new Date(t.pago?.created_at || t.created_at || t.date) >= from)
  const total = viajes.reduce((sum, t) => sum + (Number(t.pago?.amount ?? t.price) || 0), 0)

  return { viajes: viajes.length, total }
})

const resumenMes = computed(() => {
  const now = new Date()
  const from = startOfMonth(now)
  const viajes = viajesConIngreso.value.filter(t => new Date(t.pago?.created_at || t.created_at || t.date) >= from)
  const total = viajes.reduce((sum, t) => sum + (Number(t.pago?.amount ?? t.price) || 0), 0)

  return { viajes: viajes.length, total }
})

const viajesRecientes = computed(() => {

  return [...misViajesCompletados.value]
    .sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date))
    .slice(0, 10)
})

const viajesCompletadosMes = computed(() => {
  const now = new Date()
  const from = startOfMonth(now)

  return misViajesCompletados.value.filter(t => new Date(t.created_at || t.date) >= from)
})

const metricasMes = computed(() => {
  const viajes = viajesCompletadosMes.value
  const km = viajes.reduce((sum, t) => sum + (t.distance || 0), 0)

  return { km }
})

const metricasPeriodo = computed(() => {
  // Métricas del periodo “visible” del panel.
  // Nota: actualmente se calculan sobre todos los viajes con ingreso (no filtra por `periodoGrafico`).
  const viajes = viajesConIngreso.value
  const total = viajes.reduce((sum, t) => sum + (Number(t.pago?.amount ?? t.price) || 0), 0)
  const promedio = viajes.length > 0 ? total / viajes.length : 0

  const totalAsignados = viajeStore.viajesConductor.filter(t => ['accepted', 'in_progress', 'completed'].includes(t.estado)).length
  // Tasa de aceptación aproximada: ingresos / asignados.
  // Sirve como indicativo en UI; la métrica real debería definirse en backend si es crítica.
  const tasaAceptacion = totalAsignados > 0 ? Math.round((viajes.length / totalAsignados) * 100) : 0

  return {
    viajes: viajes.length,
    promedio,
    tasaAceptacion,
  }
})

const resumenPago = computed(() => {
  const init = () => ({ total: 0, viajes: 0 })
  const res = {
    cash: init(),
    card: init(),
    app: init(),
  }

  for (const t of viajesConIngreso.value) {
    const key = t.pago_method || 'cash'
    if (!res[key]) continue
    res[key].total += (Number(t.pago?.amount ?? t.price) || 0)
    res[key].viajes += 1
  }

  return res
})

const diasEnRango = computed(() => {
  // Prepara “buckets” diarios para el gráfico.
  // Se generan días consecutivos y luego se agregan importes por fecha.
  const now = new Date()
  now.setHours(0, 0, 0, 0)

  let days = 7
  if (periodoGrafico.value === '30d') days = 30
  else if (periodoGrafico.value === '90d') days = 90
  else if (periodoGrafico.value === 'year') {
    const start = new Date(now.getFullYear(), 0, 1)
    const diff = Math.floor((now - start) / (24 * 60 * 60 * 1000))
    days = diff + 1
  }

  const start = new Date(now)
  start.setDate(start.getDate() - (days - 1))

  const buckets = []
  for (let i = 0; i < days; i++) {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    const key = d.toISOString().slice(0, 10)
    const label = d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
    buckets.push({ key, label, total: 0 })
  }

  return buckets
})

const puntosGrafico = computed(() => {
  // Agrega importes a cada bucket diario y normaliza la altura (0..150) para el SVG.
  const buckets = diasEnRango.value.map(b => ({ ...b }))
  const index = new Map(buckets.map((b, i) => [b.key, i]))

  for (const t of viajesConIngreso.value) {
    const dateStr = t.pago?.created_at || t.created_at || t.date
    const d = new Date(dateStr)
    if (Number.isNaN(d.getTime())) continue
    const key = new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString().slice(0, 10)
    const i = index.get(key)
    if (i == null) continue

    const amount = Number(t.pago?.amount ?? t.price) || 0
    buckets[i].total += amount
  }

  const max = Math.max(...buckets.map(b => b.total), 1)

  return buckets.map(b => ({
    ...b,
    alto: Math.round((b.total / max) * 150),
  }))
})

const anchoBarra = computed(() => {
  const n = puntosGrafico.value.length || 1
  
  return 700 / n
})

const paddingBarra = 4

const mostrarEtiqueta = (idx) => {
  // Evita saturar el eje X cuando hay muchos días: se muestran etiquetas cada N barras.
  const n = puntosGrafico.value.length
  if (n <= 10) return true
  if (n <= 31) return idx % 3 === 0
  if (n <= 90) return idx % 7 === 0
  return idx % 14 === 0
}

onMounted(() => {
  // Cargamos viajes y estado del conductor (online/offline).
  viajeStore.obtenerViajes()
  conductorStore.obtenerEstadoConductor().catch(() => {})

  tickIntervalId = setInterval(() => {
    // Tick local para refrescar contadores basados en tiempo sin hacer polling agresivo al backend.
    nowMs.value = Date.now()

    const actual = mesActualKey()
    if (conductorStore.mesEnLinea && conductorStore.mesEnLinea !== actual) {
      // Si cambia el mes, recargamos el estado para reiniciar acumulados correctamente.
      conductorStore.obtenerEstadoConductor().catch(() => {})
    }
  }, 1000)
})

onUnmounted(() => {
  if (tickIntervalId) clearInterval(tickIntervalId)
  tickIntervalId = null
})
</script>
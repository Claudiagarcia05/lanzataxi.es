<template>
  <DisposicionAdministrador>
    <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
      <h1 class="text-3xl font-bold mb-2">Estadísticas</h1>
      <p class="text-blue-100">Bienvenido, {{ authStore.usuario?.name }}. Aquí puedes ver el resumen mensual de la plataforma</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h3 class="font-semibold text-neutral-dark">Resumen mensual</h3>
        <div class="flex items-center gap-3">
          <div>
            <label class="block text-xs text-neutral-slate mb-1">Año</label>
            <select v-model.number="anioSeleccionado" @change="cargarMensual" class="rounded-lg border-neutral-volcanic text-sm">
              <option v-for="y in anios" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-neutral-slate mb-1">Mes</label>
            <select v-model.number="mesSeleccionado" @change="cargarMensual" class="rounded-lg border-neutral-volcanic text-sm">
              <option v-for="m in 12" :key="m" :value="m">{{ String(m).padStart(2, '0') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Viajes completados</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.estadisticasMensuales.viajesCompletados }}</p>
        </div>
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Viajes cancelados</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.estadisticasMensuales.viajesCancelados }}</p>
        </div>
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Ganancias</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.ingresosMensualesFormateado }}</p>
        </div>
      </div>

      <div class="mt-6 bg-neutral-soft rounded-xl p-4">
        <p class="text-xs text-neutral-slate mb-3">Evolución diaria del mes</p>
        <div class="h-64">
          <canvas ref="lienzoGrafico"></canvas>
        </div>
      </div>
    </div>
  </DisposicionAdministrador>
</template>


<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'
import Chart from 'chart.js/auto'

// Página de Administración: panel de estadísticas mensuales.
// - Permite seleccionar año/mes y visualiza evolución diaria (Chart.js).
// - Los agregados vienen del store (`adminStore.obtenerEstadisticasMensuales`).

const authStore = useAuthStore()
const adminStore = useAdminStore()

const lienzoGrafico = ref(null)
let instanciaGrafico = null

onMounted(async () => {
  // Cargamos el mes/año inicial que el backend define como “actual”.
  await adminStore.obtenerEstadisticasMensuales({
    anio: adminStore.estadisticasMensuales.anio,
    mes: adminStore.estadisticasMensuales.mes,
  })
  anioSeleccionado.value = adminStore.estadisticasMensuales.anio
  mesSeleccionado.value = adminStore.estadisticasMensuales.mes

  // Esperamos al DOM para asegurarnos de que el <canvas> existe antes de dibujar.
  await nextTick()
  renderizarOActualizarGrafico()
})

onBeforeUnmount(() => {
  // Importante: destruir la instancia para evitar fugas de memoria al navegar.
  if (instanciaGrafico) {
    instanciaGrafico.destroy()
    instanciaGrafico = null
  }
})

const anioSeleccionado = ref(adminStore.estadisticasMensuales.anio)
const mesSeleccionado = ref(adminStore.estadisticasMensuales.mes)

const anios = computed(() => {
  const minY = adminStore.estadisticasMensuales.anioMinimo
  const maxY = adminStore.estadisticasMensuales.anioMaximo
  const out = []
  for (let y = minY; y <= maxY; y++) out.push(y)

  return out
})

const cargarMensual = async () => {
  // Re-carga al cambiar selectores y refresca el gráfico en caliente.
  await adminStore.obtenerEstadisticasMensuales({ anio: anioSeleccionado.value, mes: mesSeleccionado.value })
  await nextTick()
  renderizarOActualizarGrafico()
}

const renderizarOActualizarGrafico = () => {
  // Datos diarios calculados en backend/store para el mes seleccionado.
  const diario = adminStore.estadisticasMensuales.diario
  const etiquetas = diario?.etiquetas || []
  const viajesCompletados = diario?.viajesCompletados || []
  const viajesCancelados = diario?.viajesCancelados || []
  const ingresos = diario?.ingresos || []

  // Total de viajes = completados + cancelados (métrica operativa diaria).
  const viajes = etiquetas.map((_, idx) => Number(viajesCompletados[idx] || 0) + Number(viajesCancelados[idx] || 0))

  const lienzo = lienzoGrafico.value
  if (!lienzo) return

  const data = {
    labels: etiquetas,
    datasets: [
      {
        label: 'Ganancias (€)',
        data: ingresos,
        // Colores coherentes con branding; Chart.js requiere colores explícitos.
        borderColor: '#244194',
        backgroundColor: 'transparent',
        borderWidth: 2,
        tension: 0.25,
        pointRadius: 2,
        yAxisID: 'yRevenue',
      },
      {
        label: 'Viajes',
        data: viajes,
        borderColor: '#FDD342',
        backgroundColor: 'transparent',
        borderWidth: 2,
        tension: 0.25,
        pointRadius: 2,
        yAxisID: 'yTrips',
      },
    ],
  }

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    // Tooltip “por índice” para ver ambas series al pasar por un día.
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'bottom' },
    },
    scales: {
      x: {
        grid: { display: false },
      },
      yTrips: {
        position: 'left',
        beginAtZero: true,
        ticks: { precision: 0 },
        title: { display: true, text: 'Viajes' },
      },
      yRevenue: {
        position: 'right',
        beginAtZero: true,
        grid: { drawOnChartArea: false },
        title: { display: true, text: '€' },
      },
    },
  }

  if (!instanciaGrafico) {
    // Primera renderización.
    instanciaGrafico = new Chart(lienzo, {
      type: 'line',
      data,
      options,
    })
  } else {
    // Actualización incremental al cambiar mes/año.
    instanciaGrafico.data = data
    instanciaGrafico.options = options
    instanciaGrafico.update()
  }
}
</script>
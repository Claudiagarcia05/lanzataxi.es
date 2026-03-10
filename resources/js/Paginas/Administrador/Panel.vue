<template>
  <DisposicionAdministrador>
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-neutral-dark">Panel Administrador</h2>
      <p class="text-neutral-slate">Bienvenido, {{ authStore.usuario?.name }}. Aquí puedes ver el resumen mensual de la plataforma.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h3 class="font-semibold text-neutral-dark">Resumen mensual</h3>
        <div class="flex items-center gap-3">
          <div>
            <label class="block text-xs text-neutral-slate mb-1">Año</label>
            <select v-model.number="selectedYear" @change="cargarMensual" class="rounded-lg border-neutral-volcanic text-sm">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-neutral-slate mb-1">Mes</label>
            <select v-model.number="selectedMonth" @change="cargarMensual" class="rounded-lg border-neutral-volcanic text-sm">
              <option v-for="m in 12" :key="m" :value="m">{{ String(m).padStart(2, '0') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Viajes completados</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.estadisticasMensuales.completedTrips }}</p>
        </div>
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Viajes cancelados</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.estadisticasMensuales.cancelledTrips }}</p>
        </div>
        <div class="bg-neutral-soft rounded-xl p-4">
          <p class="text-xs text-neutral-slate">Ganancias</p>
          <p class="text-2xl font-bold text-neutral-dark">{{ adminStore.ingresosMensualesFormateado }}</p>
        </div>
      </div>
    </div>
  </DisposicionAdministrador>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'

const authStore = useAuthStore()
const adminStore = useAdminStore()

onMounted(async () => {
  await adminStore.obtenerEstadisticasMensuales({
    year: adminStore.estadisticasMensuales.year,
    month: adminStore.estadisticasMensuales.month,
  })
  selectedYear.value = adminStore.estadisticasMensuales.year
  selectedMonth.value = adminStore.estadisticasMensuales.month
})

const selectedYear = ref(adminStore.estadisticasMensuales.year)
const selectedMonth = ref(adminStore.estadisticasMensuales.month)

const years = computed(() => {
  const minY = adminStore.estadisticasMensuales.minYear
  const maxY = adminStore.estadisticasMensuales.maxYear
  const out = []
  for (let y = minY; y <= maxY; y++) out.push(y)
  return out
})

const cargarMensual = async () => {
  await adminStore.obtenerEstadisticasMensuales({ year: selectedYear.value, month: selectedMonth.value })
}
</script>

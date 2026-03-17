<template>
  <DisposicionAdministrador>
    <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
      <h1 class="text-3xl font-bold mb-2">Taxistas</h1>
      <p class="text-blue-100">Gestiona taxistas y su vehículo</p>
    </div>

    <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
    </div>
    <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
      <div class="p-6 border-b border-neutral-volcanic">
        <h3 class="font-semibold text-neutral-dark">Listado</h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-neutral-soft">
            <tr>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Nombre</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Email</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Teléfono</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Aprobación</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Estado</th>
              <th class="text-right p-4 text-xs font-medium text-neutral-slate">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in conductores" :key="c.id" class="border-b border-neutral-volcanic hover:bg-neutral-soft">
              <td class="p-4">
                <p class="font-medium text-sm text-neutral-dark">{{ c.name }}</p>
              </td>
              <td class="p-4 text-sm text-neutral-slate">{{ c.email }}</td>
              <td class="p-4 text-sm text-neutral-slate">{{ c.phone }}</td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded-full text-xs', obtenerClaseAprobacion(c.approval_status)]">
                  {{ obtenerTextoAprobacion(c.approval_status) }}
                </span>
              </td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded-full text-xs', c.is_disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                  {{ c.is_disabled ? 'De baja' : 'Activo' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button @click="abrirModalConductor(c)" class="text-sm text-lanzarote-blue hover:underline">
                  Ver
                </button>
              </td>
            </tr>

            <tr v-if="conductores.length === 0">
              <td class="p-6 text-sm text-neutral-slate" colspan="6">No hay taxistas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Conductor -->
    <div v-if="modalConductorAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg border border-neutral-volcanic">
        <div class="p-5 border-b border-neutral-volcanic flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-neutral-dark">Taxista</h3>
          </div>
          <button @click="cerrarModal" class="p-2 rounded-lg hover:bg-neutral-soft">
            <span class="text-neutral-slate font-semibold text-lg leading-none">X</span>
          </button>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-neutral-dark mb-3">Datos personales</h4>
            <div class="space-y-2 text-sm">
              <p><span class="text-neutral-slate">Nombre:</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.name }}</span></p>
              <p><span class="text-neutral-slate">Email:</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.email }}</span></p>
              <p><span class="text-neutral-slate">Teléfono:</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.phone }}</span></p>
              <p><span class="text-neutral-slate">Licencia:</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.license_number || '—' }}</span></p>
              <p>
                <span class="text-neutral-slate">Aprobación:</span>
                <span :class="['ml-2 px-2 py-1 rounded-full text-xs', obtenerClaseAprobacion(conductorSeleccionado?.approval_status)]">
                  {{ obtenerTextoAprobacion(conductorSeleccionado?.approval_status) }}
                </span>
              </p>
              <p>
                <span class="text-neutral-slate">Estado cuenta:</span>
                <span :class="['ml-2 px-2 py-1 rounded-full text-xs', conductorSeleccionado?.is_disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                  {{ conductorSeleccionado?.is_disabled ? 'De baja' : 'Activo' }}
                </span>
              </p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
              <button
                @click="descargarInformeConductor(conductorSeleccionado?.id)"
                :disabled="!conductorSeleccionado"
                class="bg-lanzarote-blue disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black"
              >
                Informe
              </button>

              <button
                v-if="conductorSeleccionado?.approval_status === 'pending'"
                @click="aprobarConductor"
                :disabled="!conductorSeleccionado"
                class="bg-green-500 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600"
              >
                Aprobar
              </button>
              <button
                v-if="conductorSeleccionado?.approval_status === 'pending'"
                @click="rechazarConductor"
                :disabled="!conductorSeleccionado"
                class="bg-red-500 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600"
              >
                Rechazar
              </button>

              <button
                @click="guardarTaxi"
                :disabled="!conductorSeleccionado || !formularioTaxi.id"
                class="bg-lanzarote-yellow disabled:opacity-50 text-black px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-blue hover:text-white"
              >
                Guardar vehículo
              </button>
            </div>
          </div>

          <div>
            <h4 class="font-semibold text-neutral-dark mb-3">Vehículo</h4>

            <div v-if="!formularioTaxi.id" class="text-sm text-neutral-slate">
              Este taxista no tiene taxi asociado.
            </div>

            <form v-else @submit.prevent="guardarTaxi" class="space-y-3">
              <div>
                <label class="block text-xs text-neutral-slate mb-1">Matrícula</label>
                <input v-model="formularioTaxi.plate" class="w-full rounded-lg border-neutral-volcanic" />
              </div>
              <div>
                <label class="block text-xs text-neutral-slate mb-1">Modelo</label>
                <input v-model="formularioTaxi.model" class="w-full rounded-lg border-neutral-volcanic" />
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">Capacidad</label>
                  <input v-model="formularioTaxi.capacity" type="number" min="1" class="w-full rounded-lg border-neutral-volcanic" />
                </div>
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">Color</label>
                  <input v-model="formularioTaxi.color" class="w-full rounded-lg border-neutral-volcanic" />
                </div>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  @click="darDeBaja(conductorSeleccionado?.user_id)"
                  :disabled="!conductorSeleccionado || conductorSeleccionado.is_disabled"
                  class="bg-red-500 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600"
                >
                  Dar de baja
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DisposicionAdministrador>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'
import axios from 'axios'
import { jsPDF } from 'jspdf'

const adminStore = useAdminStore()

const mensajeError = ref('')
const mensajeInfo = ref('')

const modalConductorAbierto = ref(false)
const conductorSeleccionado = ref(null)

const formularioTaxi = ref({
  id: null,
  plate: '',
  model: '',
  capacity: null,
  color: '',
})

onMounted(async () => {
  await adminStore.obtenerTodosLosDatos()
})

const conductores = computed(() => adminStore.conductores)

const abrirModalConductor = (conductor) => {
  conductorSeleccionado.value = conductor
  modalConductorAbierto.value = true

  const taxi = conductor.taxi || null
  const esPlatePlaceholder = String(taxi?.plate || '').startsWith('PENDIENTE-') || String(taxi?.plate || '').startsWith('TMP-')
  formularioTaxi.value = {
    id: taxi?.id ?? null,
    plate: esPlatePlaceholder ? '' : (taxi?.plate ?? ''),
    model: (taxi?.model ?? ''),
    capacity: (taxi?.capacity ?? null),
    color: taxi?.color ?? '',
  }

  if ((esPlatePlaceholder || String(taxi?.model || '').trim().toLowerCase() === 'pendiente') && Number(formularioTaxi.value.capacity || 0) === 4) {
    formularioTaxi.value.capacity = null
  }
}

const cerrarModal = () => {
  modalConductorAbierto.value = false
  conductorSeleccionado.value = null
}

const darDeBaja = async (idUsuario) => {
  if (!idUsuario) return

  mensajeError.value = ''
  mensajeInfo.value = ''

  try {
    await adminStore.darDeBajaUsuario(idUsuario)
    if (conductorSeleccionado.value?.user_id === idUsuario) {
      conductorSeleccionado.value.is_disabled = true
    }
    mensajeInfo.value = 'Taxista dado de baja correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = error.response?.data?.message || 'No se pudo dar de baja al taxista'
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const guardarTaxi = async () => {
  if (!formularioTaxi.value.id) return

  mensajeError.value = ''
  mensajeInfo.value = ''

  try {
    const payload = {}

    if (String(formularioTaxi.value.plate || '').trim()) payload.plate = String(formularioTaxi.value.plate).trim()
    if (String(formularioTaxi.value.model || '').trim()) payload.model = String(formularioTaxi.value.model).trim()
    if (formularioTaxi.value.capacity !== null && formularioTaxi.value.capacity !== '' && !Number.isNaN(Number(formularioTaxi.value.capacity))) {
      payload.capacity = Number(formularioTaxi.value.capacity)
    }
    if (formularioTaxi.value.color !== undefined) payload.color = (formularioTaxi.value.color || '').trim() || null

    await axios.put(`/api/taxis/${formularioTaxi.value.id}`, payload)

    await adminStore.obtenerConductores()
    const conductorActualizado = adminStore.conductores.find(c => c.id === conductorSeleccionado.value?.id)
    if (conductorActualizado) {
      conductorSeleccionado.value = conductorActualizado
    }

    mensajeInfo.value = 'Vehículo actualizado correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = error.response?.data?.message || 'No se pudo actualizar el vehículo'
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const aprobarConductor = async () => {
  const idConductor = conductorSeleccionado.value?.id
  if (!idConductor) return

  mensajeError.value = ''
  mensajeInfo.value = ''

  try {
    await adminStore.aprobarConductor(idConductor)
    const actualizado = adminStore.conductores.find(c => c.id === idConductor)
    if (actualizado) conductorSeleccionado.value = actualizado
    mensajeInfo.value = 'Solicitud aprobada correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = error.response?.data?.message || 'No se pudo aprobar la solicitud'
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const rechazarConductor = async () => {
  const idConductor = conductorSeleccionado.value?.id
  if (!idConductor) return

  mensajeError.value = ''
  mensajeInfo.value = ''

  try {
    await adminStore.rechazarConductor(idConductor)
    await adminStore.obtenerConductores()
    const actualizado = adminStore.conductores.find(c => c.id === idConductor)
    if (actualizado) conductorSeleccionado.value = actualizado
    mensajeInfo.value = 'Solicitud rechazada correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = error.response?.data?.message || 'No se pudo rechazar la solicitud'
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const formatearMes = (yyyyMm) => {
  const valor = String(yyyyMm || '').trim()
  const match = valor.match(/^(\d{4})-(\d{2})$/)
  if (!match) return valor || '—'

  const [_, y, m] = match
  const fecha = new Date(Number(y), Number(m) - 1, 1)
  const texto = fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' })
  return texto.charAt(0).toUpperCase() + texto.slice(1)
}

const obtenerTextoAprobacion = (estadoAprobacion) => {
  switch (estadoAprobacion) {
    case 'approved': return 'Aprobado'
    case 'rejected': return 'Rechazado'
    case 'pending':
    default: return 'Pendiente'
  }
}

const obtenerClaseAprobacion = (estadoAprobacion) => {
  switch (estadoAprobacion) {
    case 'approved': return 'bg-green-100 text-green-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    case 'pending':
    default: return 'bg-yellow-100 text-yellow-800'
  }
}

const descargarInformeConductor = async (idConductor) => {
  if (!idConductor) return

  const response = await axios.get(`/api/admin/conductors/${idConductor}/earnings-report`)
  const { conductor: conductorInfo, totals: totalesApi, months: mesesApi } = response.data

  const totalesInforme = {
    viajesCompletados: Number(totalesApi?.completedTrips || 0),
    viajesCancelados: Number(totalesApi?.cancelledTrips || 0),
    ingresos: Number(totalesApi?.revenue || 0),
  }

  const mesesInforme = Array.isArray(mesesApi)
    ? mesesApi.map((m) => ({
        mes: m.month,
        viajesCompletados: Number(m.completedTrips || 0),
        viajesCancelados: Number(m.cancelledTrips || 0),
        ingresos: Number(m.revenue || 0),
      }))
    : []

  const FAMILIA_FUENTE_PDF = 'helvetica'
  const doc = new jsPDF()
  let y = 14

  doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
  doc.setFontSize(14)
  doc.text('Informe de conductor', 14, y)
  y += 8

  doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
  doc.setFontSize(10)
  doc.text(`Nombre: ${conductorInfo?.name || ''}`, 14, y); y += 5
  doc.text(`Email: ${conductorInfo?.email || ''}`, 14, y); y += 5
  doc.text(`Teléfono: ${conductorInfo?.phone || ''}`, 14, y); y += 7

  doc.text(`Totales - Completados: ${totalesInforme.viajesCompletados} | Cancelados: ${totalesInforme.viajesCancelados} | Ganancias: ${totalesInforme.ingresos.toFixed(2)} €`, 14, y)
  y += 8

  doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
  doc.setFontSize(11)
  doc.text('Histórico por mes', 14, y)
  y += 6

  doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
  doc.setFontSize(10)
  mesesInforme.forEach((m) => {
    if (y > 285) {
      doc.addPage();
      y = 14
      doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
      doc.setFontSize(10)
    }
    const mesLabel = formatearMes(m.mes)
    doc.text(`${mesLabel}  -  Completados: ${m.viajesCompletados}  Cancelados: ${m.viajesCancelados}  Ganancias: ${m.ingresos.toFixed(2)} €`, 14, y)
    y += 5
  })

  doc.save(`informe-conductor-${idConductor}.pdf`)
}
</script>
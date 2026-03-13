<template>
  <DisposicionAdministrador>
    <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
      <h1 class="text-3xl font-bold mb-2">Taxistas</h1>
      <p class="text-blue-100">Gestiona taxistas y su vehículo</p>
    </div>

    <div v-if="errorMsg" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-red-500">{{ errorMsg }}</p>
    </div>
    <div v-if="infoMsg" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-green-500">{{ infoMsg }}</p>
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
                <span :class="['px-2 py-1 rounded-full text-xs', getApprovalClass(c.approval_status)]">
                  {{ getApprovalText(c.approval_status) }}
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
            <p class="text-sm text-neutral-slate">Datos personales no editables. Vehículo editable por admin.</p>
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
                <span :class="['ml-2 px-2 py-1 rounded-full text-xs', getApprovalClass(conductorSeleccionado?.approval_status)]">
                  {{ getApprovalText(conductorSeleccionado?.approval_status) }}
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
                class="bg-neutral-soft text-neutral-dark px-4 py-2 rounded-lg text-sm hover:bg-neutral-volcanic"
              >
                Informe
              </button>
              <button
                @click="guardarTaxi"
                :disabled="!conductorSeleccionado || !taxiForm.id"
                class="bg-lanzarote-blue disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black"
              >
                Guardar vehículo
              </button>
            </div>
          </div>

          <div>
            <h4 class="font-semibold text-neutral-dark mb-3">Vehículo</h4>

            <div v-if="!taxiForm.id" class="text-sm text-neutral-slate">
              Este taxista no tiene taxi asociado.
            </div>

            <form v-else @submit.prevent="guardarTaxi" class="space-y-3">
              <div>
                <label class="block text-xs text-neutral-slate mb-1">Matrícula</label>
                <input v-model="taxiForm.plate" class="w-full rounded-lg border-neutral-volcanic" />
              </div>
              <div>
                <label class="block text-xs text-neutral-slate mb-1">Modelo</label>
                <input v-model="taxiForm.model" class="w-full rounded-lg border-neutral-volcanic" />
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">Capacidad</label>
                  <input v-model.number="taxiForm.capacity" type="number" min="1" class="w-full rounded-lg border-neutral-volcanic" />
                </div>
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">Color</label>
                  <input v-model="taxiForm.color" class="w-full rounded-lg border-neutral-volcanic" />
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

const errorMsg = ref('')
const infoMsg = ref('')

const modalConductorAbierto = ref(false)
const conductorSeleccionado = ref(null)

const taxiForm = ref({
  id: null,
  plate: '',
  model: '',
  capacity: 4,
  color: '',
})

onMounted(async () => {
  await adminStore.fetchAllData()
})

const conductores = computed(() => adminStore.conductores)

const abrirModalConductor = (conductor) => {
  conductorSeleccionado.value = conductor
  modalConductorAbierto.value = true

  const taxi = conductor.taxi || null
  taxiForm.value = {
    id: taxi?.id ?? null,
    plate: taxi?.plate ?? '',
    model: taxi?.model ?? '',
    capacity: taxi?.capacity ?? 4,
    color: taxi?.color ?? '',
  }
}

const cerrarModal = () => {
  modalConductorAbierto.value = false
  conductorSeleccionado.value = null
}

const darDeBaja = async (userId) => {
  if (!userId) return

  errorMsg.value = ''
  infoMsg.value = ''

  try {
    await adminStore.darDeBajaUsuario(userId)
    if (conductorSeleccionado.value?.user_id === userId) {
      conductorSeleccionado.value.is_disabled = true
    }
    infoMsg.value = 'Taxista dado de baja correctamente'
    setTimeout(() => { infoMsg.value = '' }, 4000)
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'No se pudo dar de baja al taxista'
    setTimeout(() => { errorMsg.value = '' }, 4000)
  }
}

const guardarTaxi = async () => {
  if (!taxiForm.value.id) return

  errorMsg.value = ''
  infoMsg.value = ''

  try {
    await axios.put(`/api/taxis/${taxiForm.value.id}`, {
      plate: taxiForm.value.plate,
      model: taxiForm.value.model,
      capacity: taxiForm.value.capacity,
      color: taxiForm.value.color,
    })

    await adminStore.obtenerConductores()
    const updated = adminStore.conductores.find(c => c.id === conductorSeleccionado.value?.id)
    if (updated) {
      conductorSeleccionado.value = updated
    }

    infoMsg.value = 'Vehículo actualizado correctamente'
    setTimeout(() => { infoMsg.value = '' }, 4000)
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'No se pudo actualizar el vehículo'
    setTimeout(() => { errorMsg.value = '' }, 4000)
  }
}

const getApprovalText = (status) => {
  switch (status) {
    case 'approved': return 'Aprobado'
    case 'rejected': return 'Rechazado'
    case 'pending':
    default: return 'Pendiente'
  }
}

const getApprovalClass = (status) => {
  switch (status) {
    case 'approved': return 'bg-green-100 text-green-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    case 'pending':
    default: return 'bg-yellow-100 text-yellow-800'
  }
}

const descargarInformeConductor = async (conductorId) => {
  if (!conductorId) return

  const response = await axios.get(`/api/admin/conductors/${conductorId}/earnings-report`)
  const { conductor, totals, months } = response.data

  const PDF_FONT_FAMILY = 'helvetica'
  const doc = new jsPDF()
  let y = 14

  doc.setFont(PDF_FONT_FAMILY, 'bold')
  doc.setFontSize(14)
  doc.text('Informe de conductor', 14, y)
  y += 8

  doc.setFont(PDF_FONT_FAMILY, 'normal')
  doc.setFontSize(10)
  doc.text(`Nombre: ${conductor?.name || ''}`, 14, y); y += 5
  doc.text(`Email: ${conductor?.email || ''}`, 14, y); y += 5
  doc.text(`Teléfono: ${conductor?.phone || ''}`, 14, y); y += 7

  doc.text(`Totales - Completados: ${totals.completedTrips} | Cancelados: ${totals.cancelledTrips} | Ganancias: ${Number(totals.revenue || 0).toFixed(2)} €`, 14, y)
  y += 8

  doc.setFont(PDF_FONT_FAMILY, 'bold')
  doc.setFontSize(11)
  doc.text('Histórico por mes (YYYY-MM)', 14, y)
  y += 6

  doc.setFont(PDF_FONT_FAMILY, 'normal')
  doc.setFontSize(10)
  months.forEach((m) => {
    if (y > 285) {
      doc.addPage();
      y = 14
      doc.setFont(PDF_FONT_FAMILY, 'normal')
      doc.setFontSize(10)
    }
    doc.text(`${m.month}  -  Completados: ${m.completedTrips}  Cancelados: ${m.cancelledTrips}  Ganancias: ${Number(m.revenue || 0).toFixed(2)} €`, 14, y)
    y += 5
  })

  doc.save(`informe-conductor-${conductorId}.pdf`)
}
</script>

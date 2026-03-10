<template>
  <DisposicionTablero>
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-neutral-dark">Clientes</h2>
      <p class="text-neutral-slate">Gestiona clientes (datos personales solo lectura), informe de viajes y baja por admin.</p>
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
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">Estado</th>
              <th class="text-right p-4 text-xs font-medium text-neutral-slate">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in clientes" :key="u.id" class="border-b border-neutral-volcanic hover:bg-neutral-soft">
              <td class="p-4">
                <p class="font-medium text-sm text-neutral-dark">{{ u.name }}</p>
              </td>
              <td class="p-4 text-sm text-neutral-slate">{{ u.email }}</td>
              <td class="p-4 text-sm text-neutral-slate">{{ u.phone }}</td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded-full text-xs', u.is_disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                  {{ u.is_disabled ? 'De baja' : 'Activo' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button @click="abrirModalCliente(u)" class="text-sm text-lanzarote-blue hover:underline">
                  Ver
                </button>
              </td>
            </tr>

            <tr v-if="clientes.length === 0">
              <td class="p-6 text-sm text-neutral-slate" colspan="5">No hay clientes.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Cliente -->
    <div v-if="modalClienteAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg border border-neutral-volcanic">
        <div class="p-5 border-b border-neutral-volcanic flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-neutral-dark">Cliente</h3>
            <p class="text-sm text-neutral-slate">Datos personales no editables.</p>
          </div>
          <button @click="cerrarModal" class="p-2 rounded-lg hover:bg-neutral-soft">
            <span class="text-neutral-slate">Cerrar</span>
          </button>
        </div>

        <div class="p-5">
          <div class="space-y-2 text-sm">
            <p><span class="text-neutral-slate">Nombre:</span> <span class="text-neutral-dark">{{ clienteSeleccionado?.name }}</span></p>
            <p><span class="text-neutral-slate">Email:</span> <span class="text-neutral-dark">{{ clienteSeleccionado?.email }}</span></p>
            <p><span class="text-neutral-slate">Teléfono:</span> <span class="text-neutral-dark">{{ clienteSeleccionado?.phone }}</span></p>
            <p>
              <span class="text-neutral-slate">Estado cuenta:</span>
              <span :class="['ml-2 px-2 py-1 rounded-full text-xs', clienteSeleccionado?.is_disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                {{ clienteSeleccionado?.is_disabled ? 'De baja' : 'Activo' }}
              </span>
            </p>
          </div>

          <div class="mt-5 flex flex-wrap gap-2">
            <button
              @click="darDeBaja(clienteSeleccionado?.id)"
              :disabled="!clienteSeleccionado || clienteSeleccionado.is_disabled"
              class="bg-red-500 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600"
            >
              Dar de baja
            </button>
            <button
              @click="descargarInformeCliente(clienteSeleccionado?.id)"
              :disabled="!clienteSeleccionado"
              class="bg-neutral-soft text-neutral-dark px-4 py-2 rounded-lg text-sm hover:bg-neutral-volcanic"
            >
              Informe de viajes
            </button>
          </div>
        </div>
      </div>
    </div>
  </DisposicionTablero>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import DisposicionTablero from '../../Disposiciones/DisposicionTablero.vue'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'
import axios from 'axios'
import { jsPDF } from 'jspdf'

const adminStore = useAdminStore()

const modalClienteAbierto = ref(false)
const clienteSeleccionado = ref(null)

onMounted(async () => {
  await adminStore.fetchAllData()
})

const clientes = computed(() => adminStore.usuarios.filter(u => u.role === 'pasajero'))

const abrirModalCliente = (usuario) => {
  clienteSeleccionado.value = usuario
  modalClienteAbierto.value = true
}

const cerrarModal = () => {
  modalClienteAbierto.value = false
  clienteSeleccionado.value = null
}

const darDeBaja = async (userId) => {
  if (!userId) return
  if (!confirm('¿Dar de baja a este usuario?')) return
  await adminStore.darDeBajaUsuario(userId)

  if (clienteSeleccionado.value?.id === userId) {
    clienteSeleccionado.value.is_disabled = true
  }
}

const descargarInformeCliente = async (userId) => {
  if (!userId) return

  const response = await axios.get(`/api/admin/clients/${userId}/trips-report`)
  const { client, trips } = response.data

  const doc = new jsPDF()
  let y = 14

  doc.setFontSize(14)
  doc.text('Informe de cliente', 14, y)
  y += 8

  doc.setFontSize(10)
  doc.text(`Nombre: ${client?.name || ''}`, 14, y); y += 5
  doc.text(`Email: ${client?.email || ''}`, 14, y); y += 5
  doc.text(`Teléfono: ${client?.phone || ''}`, 14, y); y += 7

  doc.setFontSize(11)
  doc.text('Viajes (completados y cancelados)', 14, y)
  y += 6

  doc.setFontSize(9)
  trips.forEach((t) => {
    if (y > 285) { doc.addPage(); y = 14 }
    const fecha = String(t.created_at || '').split('T')[0]
    const estado = t.status === 'completed' ? 'completado' : t.status === 'cancelled' ? 'cancelado' : t.status
    const precio = Number(t.price || 0).toFixed(2)
    const ruta = `${t.pickup_address || ''} → ${t.dropoff_address || ''}`.trim()
    doc.text(`${fecha}  |  ${estado}  |  ${precio} €  |  ${ruta}`, 14, y)
    y += 5
  })

  doc.save(`informe-cliente-${userId}.pdf`)
}
</script>

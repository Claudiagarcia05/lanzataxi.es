<template>
  <DisposicionAdministrador>
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-neutral-dark">Clientes</h2>
      <p class="text-neutral-slate">Gestiona clientes (datos personales solo lectura), informe de viajes y baja por admin.</p>
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
    <div v-if="modalClienteAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
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
              class="bg-lanzarote-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black"
            >
              Informe de viajes
            </button>
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

  errorMsg.value = ''
  infoMsg.value = ''

  try {
    await adminStore.darDeBajaUsuario(userId)
    if (clienteSeleccionado.value?.id === userId) {
      clienteSeleccionado.value.is_disabled = true
    }
    infoMsg.value = 'Cliente dado de baja correctamente'
    setTimeout(() => { infoMsg.value = '' }, 4000)
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'No se pudo dar de baja al cliente'
    setTimeout(() => { errorMsg.value = '' }, 4000)
  }
}

const fileToDataUrl = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader()
  reader.onload = () => resolve(reader.result)
  reader.onerror = () => reject(new Error('No se pudo leer el archivo'))
  reader.readAsDataURL(file)
})

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

const descargarInformeCliente = async (userId) => {
  if (!userId) return

  errorMsg.value = ''
  infoMsg.value = ''

  try {
    const response = await axios.get(`/api/admin/clients/${userId}/trips-report`)
    const { client, trips } = response.data

    const now = new Date()
    const fechaLabel = now.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })

    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' })
    const marginX = 48
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()
    const maxWidth = pageWidth - marginX * 2
    let y = 56

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

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(11)
    doc.setTextColor(80)
    doc.text('Informe de viajes · Cliente', marginX, y)
    doc.text(`Generado: ${fechaLabel}`, pageWidth - marginX, y, { align: 'right' })
    doc.setTextColor(0)
    y += 22

    doc.setDrawColor(220)
    doc.line(marginX, y, pageWidth - marginX, y)
    y += 22

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(13)
    doc.text('Datos del cliente', marginX, y)
    y += 16

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(11)
    const labelW = 110
    const valueW = maxWidth - labelW
    const datos = [
      ['Nombre', client?.name || '—'],
      ['Email', client?.email || '—'],
      ['Teléfono', client?.phone || '—'],
    ]

    for (const [label, value] of datos) {
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

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(13)
    doc.text('Viajes (completados y cancelados)', marginX, y)
    y += 18

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(10)

    const marginBottom = 48
    const lineHeight = 14
    const safePageBottom = pageHeight - marginBottom

    for (const t of (Array.isArray(trips) ? trips : [])) {
      const fecha = String(t.created_at || '').split('T')[0]
      const estado = t.status === 'completed' ? 'completado' : t.status === 'cancelled' ? 'cancelado' : (t.status || '—')
      const precio = Number(t.price || 0).toFixed(2)
      const ruta = `${t.pickup_address || ''} → ${t.dropoff_address || ''}`.trim()
      const line = `${fecha}  |  ${estado}  |  ${precio} €  |  ${ruta}`

      const lines = doc.splitTextToSize(line, maxWidth)
      const required = lines.length * lineHeight

      if (y + required > safePageBottom) {
        doc.addPage()
        y = 56
      }

      doc.text(lines, marginX, y)
      y += required
    }

    doc.save(`informe-cliente-${userId}.pdf`)
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'No se pudo generar el informe del cliente'
    setTimeout(() => { errorMsg.value = '' }, 4000)
  }
}
</script>

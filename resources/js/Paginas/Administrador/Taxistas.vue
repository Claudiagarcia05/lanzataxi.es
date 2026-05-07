<template>
  <DisposicionAdministrador>
    <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
      <h1 class="text-3xl font-bold mb-2">{{ t('admin.drivers.title') }}</h1>
      <p class="text-blue-100">{{ t('admin.drivers.subtitle') }}</p>
    </div>

    <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
    </div>
    <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
      <div class="p-6 border-b border-neutral-volcanic">
        <h3 class="font-semibold text-neutral-dark">{{ t('admin.shared.list') }}</h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-neutral-soft">
            <tr>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">{{ t('admin.shared.name') }}</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">{{ t('admin.shared.email') }}</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">{{ t('admin.shared.phone') }}</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">{{ t('admin.drivers.approval') }}</th>
              <th class="text-left p-4 text-xs font-medium text-neutral-slate">{{ t('admin.shared.status') }}</th>
              <th class="text-right p-4 text-xs font-medium text-neutral-slate">{{ t('admin.shared.actions') }}</th>
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
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs',
                    c.is_disabled
                      ? 'bg-red-100 text-red-800'
                      : (c.is_active ? 'bg-green-100 text-green-800' : 'bg-neutral-soft text-neutral-slate'),
                  ]"
                >
                  {{ c.is_disabled ? t('admin.shared.inactive') : (c.is_active ? t('admin.drivers.connected') : t('admin.drivers.disconnected')) }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button @click="abrirModalConductor(c)" class="text-sm text-lanzarote-blue hover:underline">
                  {{ t('admin.shared.view') }}
                </button>
              </td>
            </tr>

            <tr v-if="conductores.length === 0">
              <td class="p-6 text-sm text-neutral-slate" colspan="6">{{ t('admin.drivers.empty') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="modalConductorAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg border border-neutral-volcanic">
        <div class="p-5 border-b border-neutral-volcanic flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-neutral-dark">{{ t('admin.drivers.modalTitle') }}</h3>
          </div>
          <button @click="cerrarModal" class="p-2 rounded-lg hover:bg-neutral-soft">
            <span class="text-neutral-slate font-semibold text-lg leading-none">X</span>
          </button>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-neutral-dark mb-3">{{ t('admin.drivers.personalData') }}</h4>
            <div class="space-y-2 text-sm">
              <p><span class="text-neutral-slate">{{ t('admin.shared.name') }}</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.name }}</span></p>
              <p><span class="text-neutral-slate">{{ t('admin.shared.email') }}</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.email }}</span></p>
              <p><span class="text-neutral-slate">{{ t('admin.shared.phone') }}</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.phone }}</span></p>
              <p><span class="text-neutral-slate">{{ t('admin.drivers.license') }}</span> <span class="text-neutral-dark">{{ conductorSeleccionado?.license_number || '—' }}</span></p>
              <p>
                <span class="text-neutral-slate">{{ t('admin.drivers.approvalLabel') }}</span>
                <span :class="['ml-2 px-2 py-1 rounded-full text-xs', obtenerClaseAprobacion(conductorSeleccionado?.approval_status)]">
                  {{ obtenerTextoAprobacion(conductorSeleccionado?.approval_status) }}
                </span>
              </p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
              <button
                @click="descargarInformeConductor(conductorSeleccionado?.id)"
                :disabled="!conductorSeleccionado"
                class="bg-lanzarote-blue disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black"
              >
                {{ t('admin.shared.report') }}
              </button>

              <button
                v-if="!esConductorRechazado"
                @click="guardarTaxi"
                :disabled="!conductorSeleccionado || !formularioTaxi.id"
                class="bg-lanzarote-yellow disabled:opacity-50 text-black px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-blue hover:text-white"
              >
                {{ t('admin.drivers.saveVehicle') }}
              </button>
            </div>
          </div>

          <div>
            <h4 class="font-semibold text-neutral-dark mb-3">{{ t('admin.drivers.vehicle') }}</h4>

            <div v-if="!formularioTaxi.id" class="text-sm text-neutral-slate">
              {{ t('admin.drivers.noVehicle') }}
            </div>

            <form v-else @submit.prevent="guardarTaxi" class="space-y-3">
              <div>
                <label class="block text-xs text-neutral-slate mb-1">{{ t('admin.drivers.plate') }}</label>
                <input
                  v-model="formularioTaxi.plate"
                  :disabled="esConductorRechazado"
                  maxlength="8"
                  placeholder="1234 ABC"
                  @input="formatearMatriculaInput"
                  class="w-full rounded-lg border-neutral-volcanic"
                />
              </div>
              <div>
                <label class="block text-xs text-neutral-slate mb-1">{{ t('admin.drivers.model') }}</label>
                <input v-model="formularioTaxi.model" :disabled="esConductorRechazado" class="w-full rounded-lg border-neutral-volcanic" />
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">{{ t('admin.drivers.capacity') }}</label>
                  <input v-model="formularioTaxi.capacity" :disabled="esConductorRechazado" type="number" min="1" class="w-full rounded-lg border-neutral-volcanic" />
                </div>
                <div>
                  <label class="block text-xs text-neutral-slate mb-1">{{ t('admin.drivers.color') }}</label>
                  <input v-model="formularioTaxi.color" :disabled="esConductorRechazado" class="w-full rounded-lg border-neutral-volcanic" />
                </div>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <button
                  v-if="!esConductorRechazado"
                  type="button"
                  @click="darDeBaja(conductorSeleccionado?.user_id)"
                  :disabled="!conductorSeleccionado || conductorSeleccionado.is_disabled"
                  class="bg-red-500 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600"
                >
                  {{ t('admin.shared.deactivate') }}
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAdminStore } from '../../Almacenes/almacenAdministrador.js'
import axios from 'axios'
import { jsPDF } from 'jspdf'

// Página de Administración: gestión de taxistas y sus vehículos.
// - Ver detalle del conductor, editar taxi asociado, dar de baja y generar informe (PDF).
// - Incluye refresco periódico del listado para ver estado online/disabled actualizado.

const adminStore = useAdminStore()
const { t } = useI18n()

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

let intervaloRefrescoConductores = null
let refrescoEnCurso = false

const refrescarConductores = async () => {
  // Evita solapes si el request tarda más que el intervalo.
  if (refrescoEnCurso) return

  refrescoEnCurso = true
  try {
    await adminStore.obtenerConductores()

    if (conductorSeleccionado.value?.id) {
      // Si el modal está abierto, intentamos re-sincronizar el conductor seleccionado.
      const actualizado = adminStore.conductores.find(c => c.id === conductorSeleccionado.value.id)
      if (actualizado) {
        conductorSeleccionado.value = actualizado
      }
    }
  } catch {

  } finally {
    refrescoEnCurso = false
  }
}

onMounted(async () => {
  // Carga inicial (usuarios + conductores + datos relacionados).
  await adminStore.obtenerTodosLosDatos()

  // Refresco de la lista cada 10s para mantener estado (conectado/desconectado).
  intervaloRefrescoConductores = setInterval(refrescarConductores, 10000)
})

onBeforeUnmount(() => {
  if (intervaloRefrescoConductores) {
    clearInterval(intervaloRefrescoConductores)
  }
})

const conductores = computed(() => adminStore.conductores)

const esConductorRechazado = computed(() => conductorSeleccionado.value?.approval_status === 'rejected')

const abrirModalConductor = (conductor) => {
  conductorSeleccionado.value = conductor
  modalConductorAbierto.value = true

  const taxi = conductor.taxi || null
  // Algunos registros usan placeholders mientras el taxi no está completo.
  const esPlatePlaceholder = String(taxi?.plate || '').startsWith('PENDIENTE-') || String(taxi?.plate || '').startsWith('TMP-')
  formularioTaxi.value = {
    id: taxi?.id ?? null,
    plate: esPlatePlaceholder ? '' : (taxi?.plate ?? ''),
    model: (taxi?.model ?? ''),
    capacity: (taxi?.capacity ?? null),
    color: taxi?.color ?? '',
  }

  // Limpieza UX: si el taxi viene con valores “pendientes” por defecto, dejamos el formulario vacío.
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
    // Baja administrada del usuario asociado al conductor.
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

    // Matrícula española típica: 4 números, espacio, 3 letras (formato 1234 ABC).
    const plate = String(formularioTaxi.value.plate || '').trim()
    if (plate) {
      const valido = /^\d{4}\s[A-Z]{3}$/.test(plate)
      if (!valido) {
        mensajeError.value = 'La matrícula debe tener formato 1234 ABC (4 números, espacio, 3 letras).'
        setTimeout(() => { mensajeError.value = '' }, 5000)

        return
      }
      payload.plate = plate
    }
    if (String(formularioTaxi.value.model || '').trim()) payload.model = String(formularioTaxi.value.model).trim()
    if (formularioTaxi.value.capacity !== null && formularioTaxi.value.capacity !== '' && !Number.isNaN(Number(formularioTaxi.value.capacity))) {
      payload.capacity = Number(formularioTaxi.value.capacity)
    }
    if (formularioTaxi.value.color !== undefined) payload.color = (formularioTaxi.value.color || '').trim() || null

    // Update parcial: solo enviamos los campos que el admin haya rellenado.
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

const formatearMatriculaInput = (e) => {
  // Formateo “en vivo”: limita a 4 dígitos + 3 letras, y pone espacio automático.
  const raw = String(e?.target?.value ?? formularioTaxi.value.plate ?? '')

  const digits = raw.replace(/\D/g, '').slice(0, 4)
  const letters = raw.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 3)

  let out = digits
  if (digits.length === 4 && letters.length > 0) out += ' '
  out += letters

  formularioTaxi.value.plate = out
}

const formatearMes = (yyyyMm) => {
  // Helper de formateo a "Mes año" (es-ES) cuando el backend entregue yyyy-mm.
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
    case 'approved': return t('admin.drivers.approved')
    case 'rejected': return t('admin.drivers.rejected')
    case 'pending':
    default: return t('admin.drivers.pending')
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

  // Endpoint admin que devuelve { conductor, trips } con ganancias por viaje.
  const response = await axios.get(`/api/admin/conductors/${idConductor}/earnings-report`)
  const data = response?.data
  const conductorInfo = data?.conductor || null
  const viajes = Array.isArray(data?.trips) ? data.trips : []

  const archivoADataUrl = (file) => new Promise((resolve, reject) => {
    // Utilidad: convertir a DataURL para incrustar en jsPDF.
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = () => reject(new Error('No se pudo leer el archivo'))
    reader.readAsDataURL(file)
  })

  const cargarImagenPublicaPng = async (url) => {
    // Carga el logo desde /public sin cache para evitar versiones antiguas.
    const respuesta = await fetch(url, { cache: 'no-store' })
    if (!respuesta.ok) return null
    const blob = await respuesta.blob()
    const dataUrl = await archivoADataUrl(blob)

    const dimensiones = await new Promise((resolve) => {
      const img = new Image()
      img.onload = () => resolve({ w: img.naturalWidth, h: img.naturalHeight })
      img.onerror = () => resolve(null)
      img.src = dataUrl
    })

    return { dataUrl, dims: dimensiones }
  }

  const now = new Date()
  const fechaLabel = now.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })

  const FAMILIA_FUENTE_PDF = 'helvetica'

  const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' })
  doc.setFont(FAMILIA_FUENTE_PDF, 'normal')

  const marginX = 48
  const marginTop = 56
  const marginBottom = 48
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()
  const maxWidth = pageWidth - marginX * 2

  const logo = await cargarImagenPublicaPng('/images/logo.png').catch(() => null)

  const renderizarEncabezado = ({ incluirDatosConductor } = { incluirDatosConductor: true }) => {
    // Encabezado reutilizable: en primera página incluye datos; en el resto, se omite.
    let y = marginTop

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
    doc.text('Informe de viajes · Conductor', marginX, y)
    doc.text(`Generado: ${fechaLabel}`, pageWidth - marginX, y, { align: 'right' })
    doc.setTextColor(0)
    y += 18

    doc.setDrawColor(220)
    doc.line(marginX, y, pageWidth - marginX, y)
    y += 18

    if (incluirDatosConductor) {
      doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
      doc.setFontSize(12)
      doc.text('Datos del conductor', marginX, y)
      y += 14

      doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
      doc.setFontSize(10)
      const labelW = 90
      const valueW = maxWidth - labelW
      const datos = [
        ['Nombre', conductorInfo?.name || '—'],
        ['Email', conductorInfo?.email || '—'],
        ['Teléfono', conductorInfo?.phone || '—'],
      ]

      for (const [label, value] of datos) {
        doc.setTextColor(90)
        doc.text(`${label}:`, marginX, y)
        doc.setTextColor(0)
        doc.text(String(value), marginX + labelW, y, { maxWidth: valueW })
        y += 14
      }

      y += 4
      doc.setDrawColor(220)
      doc.line(marginX, y, pageWidth - marginX, y)
      y += 18
    }

    doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
    doc.setFontSize(12)
    doc.text('Viajes', marginX, y)
    y += 14

    return y
  }

  const renderizarEncabezadoTabla = (y) => {
    // Tabla: fecha/estado/ganancia/ruta (ruta es la columna “elástica”).
    const tableX = marginX
    const rowH = 18

    const colFechaW = 80
    const colEstadoW = 90
    const colGananciaW = 80
    const colRutaW = maxWidth - colFechaW - colEstadoW - colGananciaW

    doc.setFillColor(245, 247, 250)
    doc.setDrawColor(220)
    doc.rect(tableX, y, maxWidth, rowH, 'FD')

    doc.setFont(FAMILIA_FUENTE_PDF, 'bold')
    doc.setFontSize(10)
    doc.setTextColor(60)

    const textY = y + 12
    doc.text('Fecha', tableX + 6, textY)
    doc.text('Estado', tableX + colFechaW + 6, textY)
    doc.text('Ganancia', tableX + colFechaW + colEstadoW + 6, textY)
    doc.text('Ruta', tableX + colFechaW + colEstadoW + colGananciaW + 6, textY)

    doc.setTextColor(0)
    doc.setFont(FAMILIA_FUENTE_PDF, 'normal')
    
    return {
      y: y + rowH,
      widths: { colFechaW, colEstadoW, colGananciaW, colRutaW },
      tableX,
      rowH,
    }
  }

  let y = renderizarEncabezado({ incluirDatosConductor: true })
  let filasEnPagina = 0
  let tabla = renderizarEncabezadoTabla(y)
  y = tabla.y

  const limiteInferiorSeguro = pageHeight - marginBottom
  const alturaLinea = 12

  const listaViajes = viajes
  for (const viaje of listaViajes) {
    const fecha = String(viaje.created_at || '').split('T')[0]
    const estado = viaje.status === 'completed'
      ? 'completado'
      : viaje.status === 'cancelled'
        ? 'cancelado'
        : viaje.status === 'pending'
          ? 'pendiente'
          : viaje.status === 'accepted'
            ? 'aceptado'
            : viaje.status === 'in_progress'
              ? 'en curso'
              : (viaje.status || '—')

    const gananciaNum = Number(viaje.earnings ?? viaje.price ?? 0)
    const ganancia = `${gananciaNum.toFixed(2)} €`
    const ruta = `${viaje.pickup_address || ''} - ${viaje.dropoff_address || ''}`.trim()

    const lineasRuta = doc.splitTextToSize(ruta || '—', tabla.widths.colRutaW - 12)
    const altoContenido = Math.max(tabla.rowH, lineasRuta.length * alturaLinea + 6)

    // Salto de página por límite de filas o falta de espacio vertical.
    const necesitaNuevaPaginaPorCantidad = filasEnPagina >= 7
    const necesitaNuevaPaginaPorEspacio = y + altoContenido > limiteInferiorSeguro
    if (necesitaNuevaPaginaPorCantidad || necesitaNuevaPaginaPorEspacio) {
      doc.addPage()
      y = renderizarEncabezado({ incluirDatosConductor: false })
      filasEnPagina = 0
      tabla = renderizarEncabezadoTabla(y)
      y = tabla.y
    }

    doc.setDrawColor(220)
    doc.rect(tabla.tableX, y, maxWidth, altoContenido)
    doc.line(tabla.tableX + tabla.widths.colFechaW, y, tabla.tableX + tabla.widths.colFechaW, y + altoContenido)
    doc.line(tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW, y, tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW, y + altoContenido)
    doc.line(tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW + tabla.widths.colGananciaW, y, tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW + tabla.widths.colGananciaW, y + altoContenido)

    const textY = y + 12
    doc.setFontSize(10)
    doc.text(String(fecha || '—'), tabla.tableX + 6, textY)
    doc.text(String(estado || '—'), tabla.tableX + tabla.widths.colFechaW + 6, textY)
    doc.text(String(ganancia || '—'), tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW + 6, textY)
    doc.text(lineasRuta, tabla.tableX + tabla.widths.colFechaW + tabla.widths.colEstadoW + tabla.widths.colGananciaW + 6, textY)

    y += altoContenido
    filasEnPagina += 1
  }

  doc.save(`informe-conductor-${idConductor}.pdf`)
}
</script>
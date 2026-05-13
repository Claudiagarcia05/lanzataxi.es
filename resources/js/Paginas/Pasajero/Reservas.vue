<template>
  <DisposicionPasajero>
    <div class="max-w-7xl mx-auto">
      <!--
        Historial de reservas del pasajero.
        - Muestra métricas (total, completados, gasto total).
        - Permite cancelar viajes pendientes.
        - Permite ir a seguimiento en viajes activos.
        - Permite valorar viajes completados.
        - Integra `ModalPago` para gestionar pagos pendientes si aplica.

        Importante: algunas reglas (p.ej. si una cancelación genera cobro) se reflejan en UI,
        pero el backend debe calcular y aplicar el cargo de forma definitiva.
      -->
      <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">{{ t('reservations.title') }}</h1>
        <p class="text-blue-100">{{ t('reservations.subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-neutral-dark">{{ estadisticasViajes.total }}</p>
          <p class="text-sm text-neutral-slate">{{ t('reservations.stats.total') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-green-600">{{ estadisticasViajes.completados }}</p>
          <p class="text-sm text-neutral-slate">{{ t('reservations.stats.completed') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-3xl font-bold text-lanzarote-blue">{{ totalGastado.toFixed(2) }}€</p>
          <p class="text-sm text-neutral-slate">{{ t('reservations.stats.spent') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
        </div>
        <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
        </div>
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-semibold text-neutral-dark">{{ t('reservations.historyTitle') }}</h2>
          <span class="text-sm text-neutral-slate">{{ t('reservations.historyCount', { count: viajesFiltrados.length }) }}</span>
        </div>

        <div class="space-y-4">
          <div v-for="viaje in viajesFiltrados" :key="viaje.id" class="border border-neutral-volcanic rounded-xl p-5 hover:shadow-md transition-shadow">
            <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-neutral-dark">{{ viaje.dropoff_address }}</h3>
                <p class="text-sm text-neutral-slate">→ {{ viaje.pickup_address }}</p>
              </div>
              <div class="flex items-center gap-3 mt-2 md:mt-0">
                <span class="text-sm text-neutral-slate">{{ formatearFecha(viaje.created_at) }}</span>
                <span v-if="viaje.conductor?.usuario?.name" class="text-sm font-medium text-neutral-dark">{{ viaje.conductor.usuario.name }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div>
                <p class="text-xs text-neutral-slate">{{ t('reservations.fields.price') }}</p>
                <p class="text-lg font-bold text-lanzarote-blue">{{ viaje.price?.toFixed(2) }}€</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">{{ t('reservations.fields.distance') }}</p>
                <p class="font-medium text-neutral-dark">{{ viaje.distance?.toFixed(1) }} km</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">{{ t('reservations.fields.passengers') }}</p>
                <p class="font-medium text-neutral-dark">{{ viaje.pasajeros || 1 }}</p>
              </div>
              <div>
                <p class="text-xs text-neutral-slate">{{ t('reservations.fields.status') }}</p>
                <span :class="['px-2 py-1 rounded-full text-xs font-medium inline-block', obtenerDistintivoEstado(viaje.estado).class]">
                  {{ obtenerDistintivoEstado(viaje.estado).label }}
                </span>
              </div>
            </div>

            <div v-if="['completed', 'cancelled'].includes(viaje.estado)" class="border-t border-neutral-volcanic pt-4 mt-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-neutral-slate">{{ t('reservations.payment.label') }}</span>
                  <span v-if="obtenerEstadoPago(viaje).tipo === 'paid'" class="text-green-600 font-medium">{{ t('reservations.payment.paid') }}</span>
                  <span v-else-if="obtenerEstadoPago(viaje).tipo === 'free'" class="text-neutral-slate font-medium">{{ t('reservations.payment.free') }}</span>
                  <span v-else class="text-yellow-600 font-medium">{{ t('reservations.payment.pending') }}</span>
                </div>

                <div v-if="viaje.estado === 'completed' && !viaje.valoracion" class="flex justify-end">
                  <button @click="abrirModalValoracion(viaje)" class="text-sm text-lanzarote-blue hover:text-lanzarote-yellow">
                    {{ t('reservations.rate.button') }}
                  </button>
                </div>
              </div>

              <div v-if="viaje.valoracion" class="mt-3">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-neutral-slate">{{ t('reservations.rate.yourRating') }}</span>
                  <div class="flex text-sm">
                    <template v-for="i in 5" :key="i">
                      <span :class="i <= (viaje.valoracion || 0) ? 'text-yellow-400' : 'text-gray-300'">★</span>
                    </template>
                  </div>
                </div>
                <p v-if="viaje.comment" class="text-sm text-neutral-slate mt-2">{{ viaje.comment }}</p>
              </div>
            </div>

            <div v-else-if="viaje.estado === 'pendiente'" class="border-t border-neutral-volcanic pt-4 mt-2">
              <div class="flex justify-end">
                <button @click="abrirConfirmacionCancelacion(viaje.id)" class="text-sm text-red-600 hover:text-red-800">
                  {{ t('reservations.cancel.button') }}
                </button>
              </div>
            </div>

            <div v-else-if="['accepted', 'in_progress'].includes(viaje.estado)" class="border-t border-neutral-volcanic pt-4 mt-2">
              <div class="flex justify-end">
                <button @click="irASeguimiento(viaje.id)" class="text-sm text-lanzarote-blue hover:text-lanzarote-yellow">
                  {{ t('reservations.tracking.button') }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="viajesFiltrados.length === 0" class="text-center py-12">
            <p class="text-neutral-slate">{{ t('reservations.empty') }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="mostrarModalValoracion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <h3 class="text-xl font-bold text-neutral-dark">{{ t('reservations.rate.title') }}</h3>
            <p v-if="viajeParaValorar" class="text-sm text-neutral-slate mt-1">
              {{ viajeParaValorar.pickup_address || viajeParaValorar.pickup }} → {{ viajeParaValorar.dropoff_address || viajeParaValorar.dropoff }}
            </p>
          </div>
          <button @click="cerrarModalValoracion" class="p-2 rounded-lg hover:bg-neutral-soft" :aria-label="t('common.close')">
            <span class="text-neutral-slate font-semibold text-lg leading-none">X</span>
          </button>
        </div>

        <div v-if="errorValoracion" class="mb-4 bg-red-50 border border-red-200 p-3 rounded-lg">
          <p class="text-sm font-medium text-red-600">{{ errorValoracion }}</p>
        </div>

        <div class="mb-5">
          <p class="text-sm text-neutral-slate mb-2">{{ t('reservations.rate.score') }}</p>
          <div class="flex items-center gap-2">
            <button
              v-for="i in 5"
              :key="i"
              type="button"
              @click="valoracionSeleccionada = i"
              class="text-2xl leading-none"
              :class="i <= valoracionSeleccionada ? 'text-yellow-400' : 'text-gray-300'"
              :aria-label="t('reservations.rate.ariaStar', { i })"
            >
              ★
            </button>
          </div>
        </div>

        <div class="mb-6">
          <label class="block text-sm text-neutral-slate mb-2">{{ t('reservations.rate.commentLabel') }}</label>
          <textarea
            v-model="comentarioValoracion"
            rows="4"
            maxlength="1000"
            class="w-full border border-neutral-volcanic rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-lanzarote-blue"
            :placeholder="t('reservations.rate.commentPlaceholder')"
          ></textarea>
          <p class="text-xs text-neutral-slate mt-2">{{ t('reservations.rate.commentMax') }}</p>
        </div>

        <div class="flex gap-3">
          <button @click="cerrarModalValoracion" type="button" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">
            {{ t('common.cancel') }}
          </button>
          <button @click="enviarValoracion" type="button" class="flex-1 bg-lanzarote-blue text-white py-2 rounded-lg hover:bg-lanzarote-yellow hover:text-black" :disabled="enviandoValoracion">
            {{ enviandoValoracion ? t('common.sending') : t('reservations.rate.submit') }}
          </button>
        </div>
      </div>
    </div>

    <ModalPago :show="mostrarModalPago" :viaje="viajePago" @close="mostrarModalPago = false" @success="manejarPagoExitoso"/>
  </DisposicionPasajero>
    <div v-if="idViajeConfirmacionCancelacion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">{{ t('reservations.cancel.title') }}</h3>
        <p class="text-neutral-slate mb-6">{{ textoConfirmacionCancelacion }}</p>
        <div class="flex space-x-3">
          <button @click="confirmarCancelacionViaje" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">{{ t('reservations.cancel.confirm') }}</button>
          <button @click="cancelarConfirmacionCancelacion" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">{{ t('reservations.cancel.back') }}</button>
        </div>
      </div>
    </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
const mensajeError = ref('')
const mensajeInfo = ref('')
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import ModalPago from '../../Componentes/ModalPago.vue'
import { useViajeStore } from '../../Almacenes/almacenViaje.js'
import { useCarteraStore } from '../../Almacenes/almacenCartera.js'

const viajeStore = useViajeStore()
const carteraStore = useCarteraStore()

const { t, locale } = useI18n()

const filtroSeleccionado = ref('all')


const mostrarModalPago = ref(false)
const viajePago = ref(null)
const mostrarModalValoracion = ref(false)

const viajeParaValorar = ref(null)
const valoracionSeleccionada = ref(0)
const comentarioValoracion = ref('')
const errorValoracion = ref('')
const enviandoValoracion = ref(false)

const totalGastado = computed(() => {
  // Total gastado “neto”.
  // - Suma viajes completados y cancelaciones cobrables.
  // - Resta `debt_paid` si existe (deuda ya pagada/compensada).
  let total = 0;
  let deudaPagada = 0;
  viajeStore.viajesPasajero.forEach(t => {
    const cancelacionCobrable = t.estado === 'cancelled' && t.conductorEntityId != null

    if (t.estado === 'completed' || cancelacionCobrable) {
      total += t.price || 0;
      if (t.debt_paid) deudaPagada += t.debt_paid;
    }
  });

  return total - deudaPagada;
})
const viajesFiltrados = computed(() => {
  // Filtro por estado + orden por fecha (más reciente primero).
  let viajes = [...viajeStore.viajesPasajero]

  if (filtroSeleccionado.value !== 'all') {
    viajes = viajes.filter(t => t.estado === filtroSeleccionado.value)
  }

  return viajes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const estadisticasViajes = computed(() => {
  // Contadores para las tarjetas de resumen.
  const total = viajeStore.viajesPasajero.length
  const completados = viajeStore.viajesPasajero.filter(t => t.estado === 'completed').length
  const pendientes = viajeStore.viajesPasajero.filter(t => t.estado === 'pendiente').length
  const cancelados = viajeStore.viajesPasajero.filter(t => t.estado === 'cancelled').length
  const activos = viajeStore.viajesPasajero.filter(t => ['accepted', 'in_progress'].includes(t.estado)).length

  return { total, completados, pendientes, cancelados, activos }
})

const obtenerDistintivoEstado = (estado) => {
  // Mapea estado → etiqueta + clases para la “pill” de UI.
  const badges = {
    'pendiente': { class: 'bg-yellow-100 text-yellow-800', label: t('reservations.status.pending') },
    'accepted': { class: 'bg-blue-100 text-blue-800', label: t('reservations.status.accepted') },
    'in_progress': { class: 'bg-green-100 text-green-800', label: t('reservations.status.inProgress') },
    'completed': { class: 'bg-gray-100 text-gray-800', label: t('reservations.status.completed') },
    'cancelled': { class: 'bg-red-100 text-red-800', label: t('reservations.status.cancelled') }
  }

  return badges[estado] || badges.pendiente
}

const abrirConfirmacionCancelacion = async (viajeId) => {
  // Abre el modal de confirmación y deja preparado el viaje seleccionado.
  mensajeError.value = ''
  mensajeInfo.value = ''
  idViajeConfirmacionCancelacion.value = viajeId
}
const idViajeConfirmacionCancelacion = ref(null)

const viajeConfirmacionCancelacion = computed(() => {
  const id = idViajeConfirmacionCancelacion.value
  if (!id) return null
  
  return viajeStore.viajesPasajero.find(v => v.id === id) || null
})

const textoConfirmacionCancelacion = computed(() => {
  // Texto dinámico:
  // - Si aún no se asignó conductor, la cancelación no debería generar cobro.
  // - Si ya hubo asignación, puede ser cobrable (según reglas del backend).
  const viaje = viajeConfirmacionCancelacion.value
  const esSinCobro = !viaje || (viaje.estado === 'pendiente' && viaje.conductorEntityId == null)
  if (esSinCobro) {

    return t('reservations.cancel.textNoCharge')
  }

  return t('reservations.cancel.textWithCharge')
})

const obtenerEstadoPago = (viaje) => {
  // Estado de pago que se muestra en UI.
  // - Cancelación sin conductor asignado: “gratis” (sin cobro).
  // - Pago marcado como paid: “pagado”.
  // - Resto: “pendiente” (puede requerir ModalPago según backend).
  if (viaje?.estado === 'cancelled' && viaje?.conductorEntityId == null) {

    return { tipo: 'free' }
  }

  if (viaje?.pago && viaje.pago.status === 'paid') {

    return { tipo: 'paid' }
  }

  return { tipo: 'pending' }
}

const confirmarCancelacionViaje = async () => {
  // Cancela el viaje y refresca saldo/deuda porque una cancelación puede generar cargo/deuda.
  const viajeId = idViajeConfirmacionCancelacion.value
  await viajeStore.cancelarViaje(viajeId)
  await carteraStore.obtenerSaldo()
  await carteraStore.obtenerResumenDeuda()
  mensajeInfo.value = t('reservations.cancel.success')
  setTimeout(() => { mensajeInfo.value = '' }, 4000)
  idViajeConfirmacionCancelacion.value = null
}

const cancelarConfirmacionCancelacion = () => {
  idViajeConfirmacionCancelacion.value = null
}


const abrirModalPago = (viaje) => {
  viajePago.value = viaje
  mostrarModalPago.value = true
}

const manejarPagoExitoso = async (pagoData) => {
  // Tras un pago, refrescamos viajes para que el estado se refleje correctamente.
  console.log('Pago exitoso:', pagoData)
  await viajeStore.obtenerViajes()
  mensajeInfo.value = t('reservations.payment.success')
  setTimeout(() => { mensajeInfo.value = '' }, 4000)
}

const abrirModalValoracion = (viaje) => {
  // Abre el modal para puntuar/comentar un viaje completado.
  mensajeError.value = ''
  mensajeInfo.value = ''
  errorValoracion.value = ''
  viajeParaValorar.value = viaje
  valoracionSeleccionada.value = Number(viaje?.valoracion || 0)
  comentarioValoracion.value = viaje?.comment || ''
  mostrarModalValoracion.value = true
}

const cerrarModalValoracion = () => {
  mostrarModalValoracion.value = false
  viajeParaValorar.value = null
  valoracionSeleccionada.value = 0
  comentarioValoracion.value = ''
  errorValoracion.value = ''
  enviandoValoracion.value = false
}

const enviarValoracion = async () => {
  // Guardado de valoración. Validamos rango 1..5 en frontend para UX.
  if (!viajeParaValorar.value) return

  errorValoracion.value = ''

  const rating = Number(valoracionSeleccionada.value)
  if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
    errorValoracion.value = t('reservations.rate.errors.invalidScore')

    return
  }

  enviandoValoracion.value = true
  try {
    await viajeStore.valorarViaje(viajeParaValorar.value.id, rating, comentarioValoracion.value)
    await viajeStore.obtenerViajes()
    mensajeInfo.value = t('reservations.rate.success')
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
    cerrarModalValoracion()
  } catch (e) {
    errorValoracion.value = e?.response?.data?.message || t('reservations.rate.errors.saveFailed')
  } finally {
    enviandoValoracion.value = false
  }
}


const formatearFecha = (cadenaFecha) => {
  const date = new Date(cadenaFecha)

  const localeFecha = locale.value === 'en' ? 'en-GB' : 'es-ES'
  
  return date.toLocaleDateString(localeFecha, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).replace(',', '')
}

const irASeguimiento = (viajeId) => {
  // Navegación a la página de seguimiento del viaje.
  // (Se usa location.href para asegurar carga directa de la ruta con parámetro.)
  window.location.href = `/pasajero/seguimiento/${viajeId}`
}

onMounted(() => {
  viajeStore.obtenerViajes()
  carteraStore.obtenerSaldo()
  carteraStore.obtenerResumenDeuda()
})
</script>
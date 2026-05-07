<template>
  <DisposicionPasajero>
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl shadow-sm p-8">
        <!--
          Perfil del pasajero.
          - Avatar: previsualización + subida a `/api/user/avatar`.
          - Datos personales: email/teléfono (nombre bloqueado).
          - Contraseña: reglas de fuerza básicas para UX.
          - Cartera virtual: añadir saldo (con un formulario de tarjeta simplificado) y retirar.
          - Preferencias y eliminación de cuenta.

          Importante:
          - La validación de tarjeta aquí es solo de formato (no es una pasarela real).
          - El backend debe validar autorización y datos definitivamente.
        -->
        <h1 class="text-2xl font-bold text-neutral-dark mb-6">{{ t('profile.title') }}</h1>
        <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
        </div>
        <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
        </div>

        <div class="flex justify-center mb-8">
          <div class="relative">
            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-lanzarote-blue/20">
              <img v-if="vistaPreviaAvatar || avatarUsuario" :src="vistaPreviaAvatar || avatarUsuario" :alt="authStore.usuario?.name" class="w-full h-full object-cover" @error="manejarErrorImagen" key="avatar-image">
              <div v-else class="w-full h-full bg-lanzarote-blue text-white flex items-center justify-center text-4xl font-bold">
                {{ authStore.usuario?.name?.charAt(0) || 'U' }}
              </div>
            </div>
            <label class="absolute bottom-0 right-0 bg-lanzarote-blue rounded-full p-2 shadow-lg cursor-pointer hover:bg-lanzarote-yellow transition-colors">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="hidden" accept="image/*" @change="manejarSubidaAvatar">
            </label>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-neutral-dark">{{ t('profile.personalInfo') }}</h3>
            <button v-if="!editandoPersonal" @click="iniciarEdicionPersonal" class="text-sm text-lanzarote-blue flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              <span>{{ t('profile.editInfo') }}</span>
            </button>
          </div>
          
          <div v-if="!editandoPersonal" class="space-y-3">
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">{{ t('profile.name') }}</span>
              <span class="text-neutral-dark font-medium">{{ authStore.usuario?.name }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">{{ t('profile.email') }}</span>
              <span class="text-neutral-dark">{{ authStore.usuario?.email }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">{{ t('profile.phone') }}</span>
              <span class="text-neutral-dark">{{ authStore.usuario?.phone || t('profile.notSpecified') }}</span>
            </div>
          </div>

          <div v-if="editandoPersonal" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.name') }}</label>
              <input :value="authStore.usuario?.name || ''" type="text" disabled class="w-full px-4 py-2 bg-neutral-soft border border-neutral-volcanic rounded-lg cursor-not-allowed">
              <p class="text-xs text-neutral-slate mt-1">{{ t('profile.nameCannotChange') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.email') }}</label>
              <input v-model="formulario.email" type="email" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': errorCorreo }">
              <p v-if="errorCorreo" class="text-xs text-red-500 mt-1">{{ errorCorreo }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.phone') }}</label>
              <input v-model="formulario.phone" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="9" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: 628123456" :class="{ 'border-red-500': errorTelefono }" @input="formulario.phone = formulario.phone.replace(/\D/g, '').slice(0, 9)">
              <p v-if="errorTelefono" class="text-xs text-red-500 mt-1">{{ errorTelefono }}</p>
            </div>
            <div class="flex space-x-3 pt-2">
              <button @click="guardarInfoPersonal" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black">
                {{ t('profile.saveChanges') }}
              </button>
              <button @click="cancelarEdicionPersonal" class="px-4 py-2 border border-neutral-volcanic rounded-lg hover:bg-neutral-soft">
                {{ t('profile.cancel') }}
              </button>
            </div>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">{{ t('profile.changePassword') }}</h3>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.newPassword') }}</label>
              <input v-model="contrasena.nueva" type="password" name="new_password" autocomplete="new-password" autocorrect="off" autocapitalize="none" spellcheck="false" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': contrasena.nueva && !esContrasenaFuerte }" @input="comprobarFuerzaContrasena">
              
              <div v-if="contrasena.nueva" class="mt-2">
                <div class="flex space-x-1 h-1 mb-2">
                  <div class="flex-1 h-full rounded" :class="colorFuerza(1)"></div>
                  <div class="flex-1 h-full rounded" :class="colorFuerza(2)"></div>
                  <div class="flex-1 h-full rounded" :class="colorFuerza(3)"></div>
                  <div class="flex-1 h-full rounded" :class="colorFuerza(4)"></div>
                </div>
                <p class="text-xs" :class="colorTextoFuerza">{{ mensajeFuerza }}</p>
              </div>

              <div class="mt-2 space-y-1">
                <p class="text-xs" :class="contrasena.nueva?.length >= 8 ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ {{ t('profile.passwordMin8') }}
                </p>
                <p class="text-xs" :class="/[A-Z]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ {{ t('profile.passwordUpper') }}
                </p>
                <p class="text-xs" :class="/[a-z]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ {{ t('profile.passwordLower') }}
                </p>
                <p class="text-xs" :class="/[0-9]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ {{ t('profile.passwordNumber') }}
                </p>
                <p class="text-xs" :class="/[!@#$%^&*]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ {{ t('profile.passwordSpecial') }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.confirmNewPassword') }}</label>
              <input v-model="contrasena.confirmacion" type="password" name="new_password_confirmation" autocomplete="new-password" autocorrect="off" autocapitalize="none" spellcheck="false" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': contrasena.confirmacion && contrasena.nueva !== contrasena.confirmacion }">
              <p v-if="contrasena.confirmacion && contrasena.nueva !== contrasena.confirmacion" class="text-xs text-red-500 mt-1">
                {{ t('profile.passwordsNoMatch') }}
              </p>
            </div>

            <button @click="cambiarContrasena" :disabled="!puedeCambiarContrasena" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black disabled:opacity-50 disabled:cursor-not-allowed">
              {{ t('profile.updatePassword') }}
            </button>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-neutral-dark">{{ t('profile.wallet.title') }}</h3>
            <div class="text-right">
              <p class="text-sm text-neutral-slate">{{ t('profile.wallet.balanceLabel') }}</p>
              <p class="text-3xl font-bold text-lanzarote-blue">{{ formatearMoneda(saldoCartera) }}</p>
            </div>
          </div>

          <div class="bg-lanzarote-blue/5 p-4 rounded-lg mb-4">
            <h4 class="font-medium text-neutral-dark mb-3">{{ t('profile.wallet.addFunds') }}</h4>
            <div class="space-y-4">
              <div class="flex gap-2">
                <button v-for="monto in [10, 20, 50, 100]" :key="monto" @click="recargaCartera = monto; montoPersonalizado = ''" :class="['flex-1 py-2 rounded-lg border transition-colors', recargaCartera === monto && !montoPersonalizado ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic hover:bg-lanzarote-blue/10']">
                  {{ monto }} €
                </button>
              </div>
              
              <div class="flex space-x-3">
                <input v-model="montoPersonalizado" type="number" min="5" step="1" :placeholder="t('profile.wallet.customAmount')" class="flex-1 px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" @input="recargaCartera = null">
              </div>

              <div v-if="mostrarFormularioPago" class="border-t border-neutral-volcanic pt-4 mt-4">
                <h5 class="font-medium text-neutral-dark mb-3">{{ t('profile.wallet.paymentData') }}</h5>
                <div class="space-y-3">
                  <div>
                    <label class="block text-sm text-neutral-slate mb-1">{{ t('profile.wallet.cardNumber') }}</label>
                    <input v-model="tarjeta.numero" type="text" maxlength="19" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg" :class="{ 'border-red-500': tarjeta.numero && !esNumeroTarjetaValido }" @input="formatearNumeroTarjeta">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm text-neutral-slate mb-1">{{ t('profile.wallet.expiry') }}</label>
                      <input v-model="tarjeta.caducidad" type="text" maxlength="5" placeholder="MM/AA" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg" :class="{ 'border-red-500': tarjeta.caducidad && !esCaducidadValida }" @input="formatearCaducidad">
                    </div>
                    <div>
                      <label class="block text-sm text-neutral-slate mb-1">{{ t('profile.wallet.cvv') }}</label>
                      <input v-model="tarjeta.cvv" type="text" maxlength="3" placeholder="123" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg" :class="{ 'border-red-500': tarjeta.cvv && !esCvvValido }" @input="validarCvv">
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm text-neutral-slate mb-1">{{ t('profile.wallet.cardHolder') }}</label>
                    <input v-model="tarjeta.nombre" type="text" :placeholder="t('profile.wallet.cardHolderPlaceholder')" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg" :class="{ 'border-red-500': tarjeta.nombre && !esNombreTarjetaValido }" @input="validarNombreTarjeta">
                  </div>
                </div>
              </div>

              <button @click="procesarAgregarACartera" :disabled="!puedeAgregarACartera" class="w-full bg-lanzarote-blue text-white py-3 rounded-lg hover:bg-lanzarote-yellow hover:text-black transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                {{ mostrarFormularioPago ? t('profile.wallet.confirmPayment') : t('profile.wallet.addFunds') }}
              </button>
            </div>
          </div>

          <div class="border-t border-neutral-volcanic pt-4">
            <h4 class="font-medium text-neutral-dark mb-3">{{ t('profile.wallet.withdrawTitle') }}</h4>
            <div class="flex space-x-3">
              <input v-model="montoRetiro" type="number" min="5" :max="saldoCartera" step="1" placeholder="Cantidad a retirar" class="flex-1 px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue">
              <button @click="procesarRetiro" :disabled="!puedeRetirar" class="px-6 py-2 bg-neutral-dark text-white rounded-lg hover:bg-neutral-slate disabled:opacity-50 disabled:cursor-not-allowed">
                {{ t('profile.wallet.withdrawBtn') }}
              </button>
            </div>
            <p class="text-xs text-neutral-slate mt-2">{{ t('profile.wallet.minMax', { max: formatearMoneda(saldoCartera) }) }}</p>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">{{ t('profile.preferences') }}</h3>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.email_notifications" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">{{ t('profile.emailNotif') }}</span>
            </label>
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.sms_notifications" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">{{ t('profile.smsNotif') }}</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button @click="mostrarConfirmacionEliminacion = true" class="text-red-600 hover:text-red-800 text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>{{ t('profile.deleteAccount') }}</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="mostrarConfirmacionEliminacion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">{{ t('profile.deleteConfirmTitle') }}</h3>
        <p class="text-neutral-slate mb-6">{{ t('profile.deleteConfirmText') }}</p>
        <div class="flex space-x-3">
          <button @click="eliminarCuenta" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
            {{ t('profile.deleteYes') }}
          </button>
          <button @click="mostrarConfirmacionEliminacion = false" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">
            {{ t('profile.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </DisposicionPasajero>
    <div v-if="mostrarConfirmacionRetiro" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">{{ t('profile.wallet.confirmWithdrawTitle') }}</h3>
        <p class="text-neutral-slate mb-6">{{ t('profile.wallet.confirmWithdrawText', { amount: formatearMoneda(montoRetiro) }) }}</p>
        <div class="flex space-x-3">
          <button @click="confirmarRetiro" class="flex-1 bg-lanzarote-blue text-white py-2 rounded-lg hover:bg-lanzarote-yellow hover:text-black">
            {{ t('profile.wallet.confirmYes') }}
          </button>
          <button @click="cancelarRetiro" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">
            {{ t('profile.cancel') }}
          </button>
        </div>
      </div>
    </div>
</template>


<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DisposicionPasajero from '../../Disposiciones/DisposicionPasajero.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import { useCarteraStore } from '../../Almacenes/almacenCartera.js'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const mensajeError = ref('')
const mensajeInfo = ref('')
const authStore = useAuthStore()
const carteraStore = useCarteraStore()
const { t, locale } = useI18n()

const vistaPreviaAvatar = ref(null)
const avatarUsuario = ref(null)
const mostrarConfirmacionEliminacion = ref(false)
const editandoPersonal = ref(false)
const mostrarConfirmacionRetiro = ref(false)

const recargaCartera = ref(10)
const montoPersonalizado = ref('')
const montoRetiro = ref('')
const mostrarFormularioPago = ref(false)

const errorCorreo = ref('')
const errorTelefono = ref('')

const tarjeta = reactive({
  numero: '',
  caducidad: '',
  cvv: '',
  nombre: ''
})

const formulario = reactive({
  nombre: '',
  email: '',
  phone: ''
})

const contrasena = reactive({
  nueva: '',
  confirmacion: ''
})

const fuerzaContrasena = ref(0)

const preferencias = reactive({
  email_notifications: true,
  sms_notifications: false,
  marketing_emails: false
})

const esNumeroTarjetaValido = computed(() => {
  // Validación de formato: 16 dígitos (sin Luhn, por simplicidad).
  const numeros = tarjeta.numero.replace(/\s/g, '')

  return /^\d{16}$/.test(numeros)
})

const esCaducidadValida = computed(() => {

  return /^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(tarjeta.caducidad)
})

const esCvvValido = computed(() => {

  return /^\d{3}$/.test(tarjeta.cvv)
})

const esNombreTarjetaValido = computed(() => {

  return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(tarjeta.nombre)
})

const puedeAgregarACartera = computed(() => {
  // Botón “Añadir saldo”:
  // - Si aún no se muestran datos de pago, solo validamos monto.
  // - Si se muestran datos de pago, validamos formato de tarjeta/caducidad/cvv/nombre.
  const monto = montoPersonalizado.value ? parseFloat(montoPersonalizado.value) : recargaCartera.value
  if (!monto || monto < 5) return false
  
  if (!mostrarFormularioPago.value) return true
  
  return esNumeroTarjetaValido.value && esCaducidadValida.value && esCvvValido.value && esNombreTarjetaValido.value
})

const puedeRetirar = computed(() => {
  // Retirada: mínimo 5€ y no superar el saldo disponible.
  const monto = parseFloat(montoRetiro.value)

  return monto >= 5 && monto <= carteraStore.saldo
})

const esContrasenaFuerte = computed(() => {
  // Reglas mínimas de fuerza para UX.
  // Mantiene consistencia con el “medidor”:
  // - Longitud >= 8
  // - Al menos 3 de 4 tipos: mayúscula/minúscula/número/especial
  const password = contrasena.nueva || ''
  if (password.length < 8) return false

  const tipos = [/[A-Z]/, /[a-z]/, /[0-9]/, /[!@#$%^&*]/]
    .reduce((acc, re) => acc + (re.test(password) ? 1 : 0), 0)

  return tipos >= 3
})

const puedeCambiarContrasena = computed(() => {

  return esContrasenaFuerte.value &&
         contrasena.nueva === contrasena.confirmacion
})

const saldoCartera = computed(() => carteraStore.saldo)
const transaccionesRecientes = computed(() => carteraStore.transacciones.slice(0, 5))

const formatearMoneda = (value) => {

  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value)
}

const formatearFecha = (date) => {

  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const cargarAvatarUsuario = async () => {
  // Normaliza el formato del avatar según venga del backend (ruta relativa / URL).
  try {
    if (authStore.usuario?.avatar) {
      const avatar = authStore.usuario.avatar
      avatarUsuario.value = avatar.startsWith('/storage') || avatar.startsWith('http') 
        ? avatar 
        : `/storage/${avatar}`
      
      console.log('Avatar cargado:', avatarUsuario.value)
    }
  } catch (error) {
    console.error('Error cargando avatar:', error)
    avatarUsuario.value = null
  }
}

const manejarSubidaAvatar = async (event) => {
  // Validaciones en cliente: tamaño/tipo.
  // El backend debe validar también para seguridad.
  const archivo = event.target.files[0]
  if (archivo) {
    if (archivo.size > 2 * 1024 * 1024) {
      mensajeError.value = 'La imagen no puede superar los 2MB'
      setTimeout(() => { mensajeError.value = '' }, 4000)

      return
    }

    if (!archivo.type.startsWith('image/')) {
      mensajeError.value = 'Solo se permiten imágenes'
      setTimeout(() => { mensajeError.value = '' }, 4000)

      return
    }

    const lector = new FileReader()
    lector.onload = async (e) => {
      vistaPreviaAvatar.value = e.target.result
      await guardarAvatar(archivo)
    }
    lector.readAsDataURL(archivo)
  }
}

const guardarAvatar = async (archivo) => {
  // Subida del avatar en multipart.
  // Además de authStore, se actualiza localStorage si existe para persistencia local.
  if (!archivo) return
  
  try {
    const datosFormulario = new FormData()
    datosFormulario.append('avatar', archivo)

    const respuesta = await axios.post('/api/user/avatar', datosFormulario, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    console.log('Respuesta del servidor:', respuesta.data)

    const urlAvatar = `/storage/${respuesta.data.avatar}`
    console.log('Avatar URL generada:', urlAvatar)
    
    avatarUsuario.value = urlAvatar
    
    if (authStore.usuario) {
      authStore.usuario.avatar = urlAvatar
    }
    
    const datosUsuarioAlmacenados = localStorage.getItem('usuario')
    if (datosUsuarioAlmacenados) {
      try {
        const usuarioAlmacenado = JSON.parse(datosUsuarioAlmacenados)
        usuarioAlmacenado.avatar = urlAvatar
        localStorage.setItem('usuario', JSON.stringify(usuarioAlmacenado))
      } catch (e) {
        console.warn('Error al actualizar localStorage:', e)
      }
    }
    
    setTimeout(() => {
      vistaPreviaAvatar.value = null
    }, 500)
    
  } catch (error) {
    console.error('Error al guardar avatar:', error)
    console.error('Respuesta del servidor:', error.response?.data)
    
    const mensajeErrorServidor = error.response?.data?.message || 'Error al subir la imagen'
    const infoDepuracion = error.response?.data?.debug
    
    if (infoDepuracion) {
      console.log('Debug info:', infoDepuracion)
    }
    
    mensajeError.value = mensajeErrorServidor
    setTimeout(() => { mensajeError.value = '' }, 4000)
    vistaPreviaAvatar.value = null
  }
}

const manejarErrorImagen = (event) => {
  // Si falla la carga del avatar, intentamos una URL absoluta alternativa.
  // Si sigue fallando, se usa el fallback (inicial del nombre).
  console.error('Error al cargar la imagen:', event?.target?.src)

  const avatarAuth = authStore.usuario?.avatar
  const avatarAuthAbsoluto = avatarAuth
    ? new URL(avatarAuth, window.location.origin).href
    : null

  if (avatarAuthAbsoluto && event?.target?.src && event.target.src !== avatarAuthAbsoluto) {
    event.target.src = avatarAuthAbsoluto

    return
  }

  avatarUsuario.value = null
  vistaPreviaAvatar.value = null
}

const iniciarEdicionPersonal = () => {
  // Copia datos actuales al formulario editable.
  formulario.nombre = authStore.usuario?.name || ''
  formulario.email = authStore.usuario?.email || ''
  formulario.phone = authStore.usuario?.phone || ''
  editandoPersonal.value = true
}

const cancelarEdicionPersonal = () => {
  editandoPersonal.value = false
  errorCorreo.value = ''
  errorTelefono.value = ''
}

const validarCorreo = (email) => {
  // Validación simple de email para UX (no cubre todos los RFCs).
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  return re.test(email)
}

const validarTelefono = (phone) => {
  // Teléfono español básico: 9 dígitos.
  const re = /^[0-9]{9}$/

  return re.test(phone)
}

const guardarInfoPersonal = async () => {
  // Guarda email/teléfono mediante API.
  // El backend debe comprobar unicidad de email, etc.
  errorCorreo.value = ''
  errorTelefono.value = ''
  
  if (!validarCorreo(formulario.email)) {
    errorCorreo.value = 'Email no válido'

    return
  }
  
  if (formulario.phone && !validarTelefono(formulario.phone)) {
    errorTelefono.value = 'Teléfono debe tener 9 dígitos'

    return
  }
  
  try {
    await axios.put('/api/user/profile', {
      email: formulario.email,
      phone: formulario.phone
    })
    
    authStore.usuario.email = formulario.email
    authStore.usuario.phone = formulario.phone
    
    editandoPersonal.value = false
    mensajeInfo.value = 'Información actualizada correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = 'Error al actualizar la información: ' + (error.response?.data?.message || 'Error desconocido')
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const comprobarFuerzaContrasena = () => {
  // Score (0..4) para pintar las 4 barras.
  // Reglas: 1 punto por longitud >= 8, y hasta 3 puntos por tipos (A-Z, a-z, 0-9, especial).
  const password = contrasena.nueva || ''
  if (!password) {
    fuerzaContrasena.value = 0

    return
  }

  const cumpleLongitud = password.length >= 8
  const tipos = [/[A-Z]/, /[a-z]/, /[0-9]/, /[!@#$%^&*]/]
    .reduce((acc, re) => acc + (re.test(password) ? 1 : 0), 0)

  let score = 0
  if (cumpleLongitud) score += 1
  score += Math.min(3, tipos)

  // Si no cumple longitud, no puede llegar a “fuerte”.
  if (!cumpleLongitud) score = Math.min(score, 3)

  fuerzaContrasena.value = Math.min(4, score)
}

const colorFuerza = (nivel) => {
  if (!contrasena.nueva) return 'bg-neutral-volcanic'
  if (fuerzaContrasena.value >= nivel) {
    if (fuerzaContrasena.value <= 2) return 'bg-red-500'
    if (fuerzaContrasena.value <= 3) return 'bg-yellow-500'

    return 'bg-success-jable'
  }

  return 'bg-neutral-volcanic'
}

const mensajeFuerza = computed(() => {
  locale.value
  if (!contrasena.nueva) return ''
  if (fuerzaContrasena.value <= 2) return t('profile.passwordWeak')
  if (fuerzaContrasena.value <= 3) return t('profile.passwordMedium')

  return t('profile.passwordStrong')
})

const colorTextoFuerza = computed(() => {
  if (!contrasena.nueva) return ''
  if (fuerzaContrasena.value <= 2) return 'text-red-500'
  if (fuerzaContrasena.value <= 3) return 'text-yellow-600'

  return 'text-success-jable'
})

const cambiarContrasena = async () => {
  if (!puedeCambiarContrasena.value) return
  
  try {
    await axios.put('/api/user/password', {
      new_password: contrasena.nueva,
      new_password_confirmation: contrasena.confirmacion
    })
    
    contrasena.nueva = ''
    contrasena.confirmacion = ''
    
    mensajeInfo.value = 'Contraseña actualizada correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = 'Error al actualizar la contraseña: ' + (error.response?.data?.message || 'Error desconocido')
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const formatearNumeroTarjeta = (e) => {
  let value = e.target.value.replace(/\D/g, '')
  value = value.substring(0, 16)
  
  const parts = []
  for (let i = 0; i < value.length; i += 4) {
    parts.push(value.substring(i, i + 4))
  }
  tarjeta.numero = parts.join(' ')
}

const formatearCaducidad = (e) => {
  let value = e.target.value.replace(/\D/g, '')
  value = value.substring(0, 4)
  
  if (value.length >= 2) {
    tarjeta.caducidad = value.substring(0, 2) + '/' + value.substring(2)
  } else {
    tarjeta.caducidad = value
  }
}

const validarCvv = (e) => {
  tarjeta.cvv = e.target.value.replace(/\D/g, '').substring(0, 3)
}

const validarNombreTarjeta = (e) => {
  tarjeta.nombre = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')
}

const procesarAgregarACartera = async () => {
  // Flujo en dos pasos:
  // 1) al pulsar “Añadir saldo” se muestran datos de pago
  // 2) al confirmar, se delega en el store (simulación/llamada backend)
  const monto = montoPersonalizado.value ? parseFloat(montoPersonalizado.value) : recargaCartera.value
  
  if (monto < 5) {
    mensajeError.value = 'El mínimo para añadir es 5€'
    setTimeout(() => { mensajeError.value = '' }, 4000)

    return
  }

  if (!mostrarFormularioPago.value) {
    mostrarFormularioPago.value = true

    return
  }

  const resultado = await carteraStore.anadirFondos(monto)
  
  if (resultado.success) {
    mensajeInfo.value = `Se han añadido ${formatearMoneda(monto)} a tu cartera`
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
    mostrarFormularioPago.value = false
    tarjeta.numero = ''
    tarjeta.caducidad = ''
    tarjeta.cvv = ''
    tarjeta.nombre = ''
    montoPersonalizado.value = ''
    recargaCartera.value = 10
  } else {
    mensajeError.value = 'Error al procesar el pago'
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const procesarRetiro = async () => {
  // Antes de retirar mostramos confirmación.
  const monto = parseFloat(montoRetiro.value)
  
  if (monto < 5) {
    mensajeError.value = 'El mínimo para retirar es 5€'
    setTimeout(() => { mensajeError.value = '' }, 4000)

    return
  }

  if (monto > carteraStore.saldo) {
    mensajeError.value = 'No tienes suficiente saldo'
    setTimeout(() => { mensajeError.value = '' }, 4000)

    return
  }

  mostrarConfirmacionRetiro.value = true
}

const confirmarRetiro = async () => {
  // Ejecuta la retirada (store/back) y muestra mensaje informativo.
  const monto = parseFloat(montoRetiro.value)
  const resultado = await carteraStore.retirarFondos(monto)
  if (resultado.success) {
    mensajeInfo.value = 'Solicitud de retirada procesada. El dinero se transferirá a tu cuenta en 2-3 días hábiles.'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
    montoRetiro.value = ''
  }
  mostrarConfirmacionRetiro.value = false
}

const cancelarRetiro = () => {
  mostrarConfirmacionRetiro.value = false
}

const eliminarCuenta = async () => {
  // Eliminar cuenta: intentamos borrar en backend y luego cerramos sesión sí o sí.
  // Esto evita que el usuario quede en un estado inconsistente de autenticación.
  try {
    await axios.delete('/api/user')
  } catch (error) {
  }
  await authStore.cerrarSesion()
  router.visit('/')
}

onMounted(async () => {
  // Carga inicial: sincroniza usuario, avatar y datos de cartera.
  await authStore.sincronizarUsuario()
  if (authStore.usuario) {
    formulario.nombre = authStore.usuario.name || ''
    formulario.email = authStore.usuario.email || ''
    formulario.phone = authStore.usuario.phone || ''
  }
  cargarAvatarUsuario()
  carteraStore.obtenerSaldo()
  carteraStore.obtenerTransacciones()
  if (authStore.usuario?.preferences) {
    Object.assign(preferencias, authStore.usuario.preferences)
  }
})
</script>
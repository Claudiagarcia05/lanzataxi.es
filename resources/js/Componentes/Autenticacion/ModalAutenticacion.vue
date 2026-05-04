<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>

      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-8 transform transition-all" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" aria-describedby="auth-modal-desc" tabindex="-1">
          <button type="button" @click="cerrarModal" class="absolute top-4 right-4 text-neutral-slate hover:text-neutral-dark" aria-label="Cerrar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <div class="text-center mb-8">
            <img src="/images/logo.png" alt="LanzaTaxi" class="h-20 mx-auto mb-2" loading="lazy" decoding="async">
            <h2 id="auth-modal-title" class="text-2xl font-bold text-lanzarote-blue">LanzaTaxi</h2>
            <p id="auth-modal-desc" class="text-neutral-slate text-sm mt-1">
              {{ esInicioSesion ? t('auth.welcomeBack') : t('auth.createAccount') }}
            </p>
          </div>

          <div v-if="error" :class="[esInicioSesion && error === t('auth.messages.registerSuccess') ? 'mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-600 text-sm' : 'mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm']" aria-live="polite">
            {{ error }}
          </div>

          <form @submit.prevent="enviarFormulario" class="space-y-4">
            <div v-if="!esInicioSesion">
              <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('auth.fields.fullName') }}</label>
              <input v-model="datosFormulario.name" type="text" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" :placeholder="t('auth.placeholders.fullName')">
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('auth.fields.email') }}</label>
              <input v-model="datosFormulario.email" type="email" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" :placeholder="t('auth.placeholders.email')">
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('auth.fields.password') }}</label>
              <input v-model="datosFormulario.password" type="password" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" :placeholder="t('auth.placeholders.password')">
            </div>

            <div v-if="!esInicioSesion">
              <label class="block text-sm font-medium text-neutral-dark mb-1">{{ t('auth.fields.confirmPassword') }}</label>
              <input v-model="datosFormulario.password_confirmation" type="password" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" :placeholder="t('auth.placeholders.confirmPassword')">
            </div>

            <div v-if="!esInicioSesion">
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('auth.fields.userType') }}</label>
              <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="datosFormulario.role = 'pasajero'" :class="['p-2 rounded-lg border text-sm transition-all', datosFormulario.role === 'pasajero' ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic text-neutral-dark hover:border-lanzarote-blue']">
                  {{ t('auth.roles.passenger') }}
                </button>
                <button type="button" @click="datosFormulario.role = 'conductor'" :class="[ 'p-2 rounded-lg border text-sm transition-all', datosFormulario.role === 'conductor' ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic text-neutral-dark hover:border-lanzarote-blue']">
                  {{ t('auth.roles.driver') }}
                </button>
              </div>
            </div>

            <button type="submit" :disabled="cargando" class="w-full bg-lanzarote-blue text-white py-3 px-4 rounded-lg font-semibold hover:bg-lanzarote-yellow hover:text-black transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-6" :aria-busy="cargando ? 'true' : 'false'">
              <span v-if="!cargando">{{ esInicioSesion ? t('auth.actions.signIn') : t('auth.actions.createAccount') }}</span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ t('common.processing') }}
              </span>
            </button>

            <p class="text-center text-sm text-neutral-slate mt-4">
              {{ esInicioSesion ? t('auth.switch.noAccount') : t('auth.switch.haveAccount') }}
              <button type="button" @click="esInicioSesion = !esInicioSesion; error = ''" class="text-lanzarote-blue font-semibold hover:underline ml-1">
                {{ esInicioSesion ? t('auth.switch.signUp') : t('auth.switch.signIn') }}
              </button>
            </p>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>


<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { executeRecaptchaV3 } from '../../recaptchaV3'

/**
 * Modal de autenticación (login/registro).
 *
 * Puntos clave:
 * - Incluye validación básica en cliente (campos requeridos y coherencia).
 * - Integra reCAPTCHA v3 si hay `VITE_RECAPTCHA_SITE_KEY` configurada.
 * - Tras login, guarda token y redirige a `/auth/session-login` para convertir
 *   token (Sanctum) a sesión web (patrón útil con Inertia).
 * - En registro aplica reglas de dominio por rol (legacy de la app).
 */

defineProps({
  modelValue: Boolean
})

const emit = defineEmits(['update:modelValue'])

const esInicioSesion = ref(true)
const cargando = ref(false)
const error = ref('')

const { t } = useI18n()

const datosFormulario = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'pasajero'
})

const cerrarModal = () => {
  // Cierra el modal (v-model) y resetea formulario/errores.
  emit('update:modelValue', false)
  reiniciarFormulario()
}

const reiniciarFormulario = () => {
  datosFormulario.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'pasajero'
  }
  error.value = ''
}

const validarFormulario = () => {
  // Validación de UX: evita requests innecesarias y muestra mensajes i18n.
  if (esInicioSesion.value) {
    if (!datosFormulario.value.email.trim() || !datosFormulario.value.password) {
      error.value = t('auth.errors.emailPasswordRequired')

      return false
    }
  } else {
    if (!datosFormulario.value.name.trim()) {
      error.value = t('auth.errors.nameRequired')

      return false
    }
    if (!datosFormulario.value.email.trim()) {
      error.value = t('auth.errors.emailRequired')

      return false
    }
    if (!datosFormulario.value.password) {
      error.value = t('auth.errors.passwordRequired')

      return false
    }
    if (!datosFormulario.value.password_confirmation) {
      error.value = t('auth.errors.confirmPasswordRequired')

      return false
    }
    if (datosFormulario.value.password !== datosFormulario.value.password_confirmation) {
      error.value = t('auth.errors.passwordsMismatch')

      return false
    }
    if (datosFormulario.value.password.length < 6) {
      error.value = t('auth.errors.passwordMin', { min: 6 })

      return false
    }

    const correo = (datosFormulario.value.email || '').trim().toLowerCase()
    const rol = datosFormulario.value.role || 'pasajero'
    const esCorreoAdmin = correo.endsWith('@admin.es')
    const esCorreoConductor = correo.endsWith('@taxi.es')

    const mensajeGenericoCredenciales = t('auth.errors.invalidCredentials')

    if (esCorreoAdmin) {
      error.value = mensajeGenericoCredenciales

      return false
    }

    if (rol === 'conductor' && !esCorreoConductor) {
      error.value = mensajeGenericoCredenciales

      return false
    }

    if (rol !== 'conductor' && esCorreoConductor) {
      error.value = mensajeGenericoCredenciales

      return false
    }
  }

  return true
}

const enviarFormulario = async () => {
  // Envía login o registro. Si hay reCAPTCHA habilitado, adjunta `recaptcha_token`.
  if (!validarFormulario()) {

    return
  }

  cargando.value = true
  error.value = ''

  datosFormulario.value.email = datosFormulario.value.email.trim()
  datosFormulario.value.name = datosFormulario.value.name.trim()

  const accionRecaptcha = esInicioSesion.value ? 'login' : 'register'
  let recaptchaToken = null

  try {
    recaptchaToken = await executeRecaptchaV3(accionRecaptcha)
  } catch (e) {
    // Si hay site key configurada pero la ejecución falla, se considera error.
    if (import.meta.env.VITE_RECAPTCHA_SITE_KEY) {
      error.value = t('auth.errors.recaptchaFailed')

      cargando.value = false

      return
    }
  }

  try {
    if (esInicioSesion.value) {
      // Login: backend devuelve token + user.
      const respuesta = await axios.post('/api/login', {
        email: datosFormulario.value.email,
        password: datosFormulario.value.password,
        recaptcha_token: recaptchaToken,
      })

      const token = respuesta.data?.token
      const usuario = respuesta.data?.user

      if (!token) {
        error.value = t('auth.errors.loginFailed')

        return
      }

      localStorage.setItem('token', token)
      if (usuario) {
        localStorage.setItem('usuario', JSON.stringify(usuario))
      }
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // Redirección: crea sesión web desde el token (para rutas Inertia/protegidas).
      window.location.href = `/auth/session-login?token=${encodeURIComponent(token)}`

      return
    }

    const respuesta = await axios.post('/api/register', {
      name: datosFormulario.value.name,
      email: datosFormulario.value.email,
      password: datosFormulario.value.password,
      password_confirmation: datosFormulario.value.password_confirmation,
      role: datosFormulario.value.role,
      phone: datosFormulario.value.phone?.trim() || null,
      recaptcha_token: recaptchaToken,
    })

    if (!respuesta.data?.user) {
      error.value = t('auth.errors.registerFailed')
      
      return
    }

    error.value = ''
    esInicioSesion.value = true
    datosFormulario.value = {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: 'pasajero'
    }
    error.value = t('auth.messages.registerSuccess')
  } catch (e) {
    const mensaje = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})?.flat()?.[0]
      || t('auth.errors.generic')

    error.value = mensaje
  } finally {
    cargando.value = false
  }
}
</script>
<template>
  <div class="min-h-screen relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-amber-50 px-4 py-10 flex items-center justify-center">
    <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-lanzarote-blue/10 blur-3xl"></div>
    <div class="absolute -bottom-28 -right-20 h-80 w-80 rounded-full bg-lanzarote-yellow/20 blur-3xl"></div>

    <div class="relative w-full max-w-2xl rounded-[2rem] border border-white/70 bg-white/90 backdrop-blur-xl shadow-[0_20px_60px_rgba(15,23,42,0.12)] p-6 sm:p-10">
      <div class="mb-8 text-center">
        <img src="/images/logo.png" alt="LanzaTaxi" class="h-16 mx-auto mb-4" loading="lazy" decoding="async">
        <p class="text-sm font-semibold tracking-[0.2em] text-lanzarote-blue uppercase">{{ t('auth.recovery.kicker') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-black text-slate-900">{{ t('auth.recovery.title') }}</h1>
        <p class="mt-3 text-slate-600 max-w-xl mx-auto">{{ t('auth.recovery.subtitle') }}</p>
      </div>

      <div v-if="mensaje" class="mb-6 rounded-2xl border px-4 py-3 text-sm" :class="mensajeEsError ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
        {{ mensaje }}
      </div>

      <div class="mb-8 flex items-center justify-between gap-3 text-xs sm:text-sm">
        <div v-for="paso in pasos" :key="paso.numero" class="flex-1 rounded-2xl border px-3 py-2 text-center" :class="pasoActivo >= paso.numero ? 'border-lanzarote-blue bg-lanzarote-blue text-white' : 'border-slate-200 bg-white text-slate-500'">
          <p class="font-semibold">{{ paso.numero }}</p>
          <p class="mt-1">{{ paso.titulo }}</p>
        </div>
      </div>

      <form v-if="pasoActivo === 1" class="space-y-5" @submit.prevent="enviarCodigo">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('auth.recovery.emailLabel') }}</label>
          <input v-model="email" type="email" autocomplete="email" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-lanzarote-blue focus:ring-4 focus:ring-lanzarote-blue/10" :placeholder="t('auth.recovery.emailPlaceholder')">
        </div>

        <button type="submit" :disabled="cargando" class="w-full rounded-2xl bg-lanzarote-blue px-4 py-3 font-semibold text-white transition hover:bg-lanzarote-yellow hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60">
          {{ cargando ? t('common.processing') : t('auth.recovery.sendCode') }}
        </button>
      </form>

      <form v-else-if="pasoActivo === 2" class="space-y-5" @submit.prevent="verificarCodigo">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
          {{ t('auth.recovery.codeSentTo', { email }) }}
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('auth.recovery.codeLabel') }}</label>
          <input v-model="codigo" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-center text-2xl tracking-[0.5em] font-bold outline-none transition focus:border-lanzarote-blue focus:ring-4 focus:ring-lanzarote-blue/10" :placeholder="t('auth.recovery.codePlaceholder')" @input="codigo = codigo.replace(/\D/g, '').slice(0, 5)">
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <button type="button" class="rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50" @click="reiniciarFlujo">
            {{ t('auth.recovery.changeEmail') }}
          </button>
          <button type="submit" :disabled="cargando" class="flex-1 rounded-2xl bg-lanzarote-blue px-4 py-3 font-semibold text-white transition hover:bg-lanzarote-yellow hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60">
            {{ cargando ? t('common.processing') : t('auth.recovery.verifyCode') }}
          </button>
        </div>
      </form>

      <form v-else class="space-y-5" @submit.prevent="restablecerContrasena">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
          {{ t('auth.recovery.codeVerified') }}
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('profile.newPassword') }}</label>
          <input v-model="password" type="password" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-lanzarote-blue focus:ring-4 focus:ring-lanzarote-blue/10" :class="{ 'border-red-500': password && !esContrasenaSegura }" @input="comprobarSeguridad">
          <div v-if="password" class="mt-3 space-y-1 text-xs">
            <p :class="password.length >= 8 ? 'text-emerald-600' : 'text-slate-500'">✓ {{ t('profile.passwordMin8') }}</p>
            <p :class="/[A-Z]/.test(password) ? 'text-emerald-600' : 'text-slate-500'">✓ {{ t('profile.passwordUpper') }}</p>
            <p :class="/[a-z]/.test(password) ? 'text-emerald-600' : 'text-slate-500'">✓ {{ t('profile.passwordLower') }}</p>
            <p :class="/[0-9]/.test(password) ? 'text-emerald-600' : 'text-slate-500'">✓ {{ t('profile.passwordNumber') }}</p>
            <p :class="/[!@#$%^&*]/.test(password) ? 'text-emerald-600' : 'text-slate-500'">✓ {{ t('profile.passwordSpecial') }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('profile.confirmNewPassword') }}</label>
          <input v-model="passwordConfirmation" type="password" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-lanzarote-blue focus:ring-4 focus:ring-lanzarote-blue/10" :class="{ 'border-red-500': passwordConfirmation && password !== passwordConfirmation }">
          <p v-if="passwordConfirmation && password !== passwordConfirmation" class="mt-2 text-xs text-red-600">{{ t('profile.passwordsNoMatch') }}</p>
        </div>

        <button type="submit" :disabled="cargando || !esContrasenaSegura || password !== passwordConfirmation" class="w-full rounded-2xl bg-lanzarote-blue px-4 py-3 font-semibold text-white transition hover:bg-lanzarote-yellow hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60">
          {{ cargando ? t('common.processing') : t('auth.recovery.resetPassword') }}
        </button>
      </form>

      <div class="mt-8 text-center text-sm text-slate-600">
        <button type="button" class="font-semibold text-lanzarote-blue hover:underline" @click="volverALogin">{{ t('auth.recovery.backToLogin') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const pasoActivo = ref(1)
const email = ref('')
const codigo = ref('')
const tokenVerificacion = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const cargando = ref(false)
const mensaje = ref('')
const mensajeEsError = ref(false)

const pasos = computed(() => ([
  { numero: 1, titulo: t('auth.recovery.stepRequest') },
  { numero: 2, titulo: t('auth.recovery.stepCode') },
  { numero: 3, titulo: t('auth.recovery.stepPassword') },
]))

const esContrasenaSegura = computed(() => {
  const valor = password.value || ''

  return valor.length >= 8
    && /[A-Z]/.test(valor)
    && /[a-z]/.test(valor)
    && /[0-9]/.test(valor)
    && /[!@#$%^&*]/.test(valor)
})

const mostrarMensaje = (texto, esError = false) => {
  mensaje.value = texto
  mensajeEsError.value = esError
}

const limpiarMensaje = () => {
  mensaje.value = ''
  mensajeEsError.value = false
}

const normalizarEmail = () => {
  email.value = email.value.trim().toLowerCase()
}

const reiniciarFlujo = () => {
  pasoActivo.value = 1
  codigo.value = ''
  tokenVerificacion.value = ''
  password.value = ''
  passwordConfirmation.value = ''
  limpiarMensaje()
}

const volverALogin = () => {
  router.visit('/login')
}

const enviarCodigo = async () => {
  normalizarEmail()

  if (!email.value) {
    mostrarMensaje(t('auth.recovery.emailRequired'), true)

    return
  }

  cargando.value = true
  limpiarMensaje()

  try {
    const respuesta = await axios.post('/forgot-password', {
      email: email.value,
    })

    pasoActivo.value = 2
    mostrarMensaje(respuesta.data?.message || t('auth.recovery.codeSent'), false)
  } catch (error) {
    mostrarMensaje(error.response?.data?.message || t('auth.recovery.sendError'), true)
  } finally {
    cargando.value = false
  }
}

const verificarCodigo = async () => {
  normalizarEmail()

  if (codigo.value.length !== 5) {
    mostrarMensaje(t('auth.recovery.codeRequired'), true)

    return
  }

  cargando.value = true
  limpiarMensaje()

  try {
    const respuesta = await axios.post('/forgot-password/verify', {
      email: email.value,
      code: codigo.value,
    })

    tokenVerificacion.value = respuesta.data?.token || ''
    pasoActivo.value = 3
    mostrarMensaje(respuesta.data?.message || t('auth.recovery.codeVerified'), false)
  } catch (error) {
    mostrarMensaje(error.response?.data?.message || t('auth.recovery.invalidCode'), true)
  } finally {
    cargando.value = false
  }
}

const comprobarSeguridad = () => {
  limpiarMensaje()
}

const restablecerContrasena = async () => {
  if (!tokenVerificacion.value) {
    mostrarMensaje(t('auth.recovery.tokenMissing'), true)

    return
  }

  if (!esContrasenaSegura.value || password.value !== passwordConfirmation.value) {
    mostrarMensaje(t('auth.recovery.passwordRequirements'), true)

    return
  }

  cargando.value = true
  limpiarMensaje()

  try {
    const respuesta = await axios.post('/forgot-password/reset', {
      email: email.value,
      token: tokenVerificacion.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    mostrarMensaje(respuesta.data?.message || t('auth.recovery.passwordUpdated'), false)
    setTimeout(() => {
      router.visit('/login')
    }, 1200)
  } catch (error) {
    mostrarMensaje(error.response?.data?.message || t('auth.recovery.resetError'), true)
  } finally {
    cargando.value = false
  }
}
</script>
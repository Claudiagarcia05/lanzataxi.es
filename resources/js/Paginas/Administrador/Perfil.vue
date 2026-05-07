<template>
  <DisposicionAdministrador>
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl shadow-sm p-8">
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
              <img v-if="vistaPreviaAvatar || avatarUsuario" :src="vistaPreviaAvatar || avatarUsuario" :alt="perfil?.name" class="w-full h-full object-cover" @error="manejarErrorImagen" key="avatar-image">
              <div v-else class="w-full h-full bg-lanzarote-blue text-white flex items-center justify-center text-4xl font-bold">
                {{ perfil?.name?.charAt(0) || 'A' }}
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
              <span class="text-neutral-dark font-medium">{{ perfil?.name }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">{{ t('profile.email') }}</span>
              <span class="text-neutral-dark">{{ perfil?.email }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">{{ t('profile.phone') }}</span>
              <span class="text-neutral-dark">{{ perfil?.phone || t('profile.notSpecified') }}</span>
            </div>
          </div>
          <div v-if="editandoPersonal" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.name') }}</label>
              <input :value="perfil?.name || ''" type="text" disabled class="w-full px-4 py-2 bg-neutral-soft border border-neutral-volcanic rounded-lg cursor-not-allowed">
              <p class="text-xs text-neutral-slate mt-1">{{ t('profile.nameCannotChange') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.email') }}</label>
              <input v-model="form.email" type="email" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': errorCorreo }">
              <p v-if="errorCorreo" class="text-xs text-red-500 mt-1">{{ errorCorreo }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">{{ t('profile.phone') }}</label>
              <input v-model="form.phone" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="9" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: 628123456" :class="{ 'border-red-500': errorTelefono }" @input="form.phone = form.phone.replace(/\D/g, '').slice(0, 9)">
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

        <div class="pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">{{ t('profile.preferences') }}</h3>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.notificacionesCorreo" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">{{ t('profile.emailNotif') }}</span>
            </label>
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.notificacionesSms" class="w-4 h-4 text-lanzarote-blue">
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
  </DisposicionAdministrador>
</template>


<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

// Página de Administración: perfil del usuario (admin).
// - Permite: actualizar email/teléfono, subir avatar, cambiar contraseña y eliminar cuenta.
// - Nota: validaciones del lado cliente son UX; el backend debe validar y autorizar.

const authStore = useAuthStore()
const { t, locale } = useI18n()
const perfil = computed(() => authStore.usuario)

const mensajeError = ref('')
const mensajeInfo = ref('')
const vistaPreviaAvatar = ref(null)
const mostrarConfirmacionEliminacion = ref(false)

const normalizarUrlAvatar = (avatar) => {
  // Normaliza diferentes formatos que pueden venir de backend (ruta relativa/absoluta).
  // Objetivo: que la UI siempre reciba una URL navegable.
  if (typeof avatar !== 'string') return null

  const recortado = avatar.trim()
  if (!recortado) return null

  if (/^https?:\/\//i.test(recortado)) return recortado
  if (recortado.startsWith('/storage/')) return recortado
  if (recortado.startsWith('storage/')) return `/${recortado}`
  if (recortado.startsWith('/avatars/')) return `/storage${recortado}`
  if (!recortado.startsWith('/')) return `/storage/${recortado}`

  return recortado
}

const avatarUsuario = ref(normalizarUrlAvatar(perfil.value?.avatar))
const editandoPersonal = ref(false)
const errorCorreo = ref('')
const errorTelefono = ref('')

const form = reactive({
  email: '',
  phone: ''
})

const contrasena = reactive({
  nueva: '',
  confirmacion: ''
})

const fuerzaContrasena = ref(0)

const preferencias = reactive({
  notificacionesCorreo: true,
  notificacionesSms: false
})

const esContrasenaFuerte = computed(() => {
  // Política de fortaleza en frontend (mínimos razonables).
  // Consistente con el medidor: longitud >= 8 y 3/4 tipos.
  const password = contrasena.nueva || ''
  if (password.length < 8) return false

  const tipos = [/[A-Z]/, /[a-z]/, /[0-9]/, /[!@#$%^&*]/]
    .reduce((acc, re) => acc + (re.test(password) ? 1 : 0), 0)

  return tipos >= 3
})

const puedeCambiarContrasena = computed(() => {
  // Habilita el botón solo cuando cumple fortaleza y coincide confirmación.
  return esContrasenaFuerte.value &&
         contrasena.nueva === contrasena.confirmacion
})

const comprobarFuerzaContrasena = () => {
  // Score (0..4) para las 4 barras.
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

const manejarSubidaAvatar = async (event) => {
  const archivo = event.target.files[0]
  if (archivo) {
    // Límite para evitar cargas excesivas y mala experiencia.
    if (archivo.size > 2 * 1024 * 1024) {
      mensajeError.value = 'La imagen no puede superar los 2MB'
      setTimeout(() => { mensajeError.value = '' }, 4000)

      return
    }
    // Comprobación rápida del tipo de archivo (el backend debe validar igual).
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
  if (!archivo) return
  try {
    // Subida multipart para el avatar. El endpoint devuelve la ruta/URL del avatar.
    const datosFormulario = new FormData()
    datosFormulario.append('avatar', archivo)
    const response = await axios.post('/api/user/avatar', datosFormulario, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    const urlAvatar = normalizarUrlAvatar(response.data.avatar)
    avatarUsuario.value = urlAvatar

    if (authStore.usuario) {
      authStore.usuario.avatar = urlAvatar
    }

    setTimeout(() => {
      vistaPreviaAvatar.value = null
    }, 500)
  } catch (error) {
    mensajeError.value = error.response?.data?.message || 'Error al subir la imagen'
    setTimeout(() => { mensajeError.value = '' }, 4000)
    vistaPreviaAvatar.value = null
  }
}

const manejarErrorImagen = () => {
  avatarUsuario.value = null
  vistaPreviaAvatar.value = null
}

const iniciarEdicionPersonal = () => {
  form.email = perfil.value?.email || ''
  form.phone = perfil.value?.phone || ''
  editandoPersonal.value = true
}

const cancelarEdicionPersonal = () => {
  editandoPersonal.value = false
  errorCorreo.value = ''
  errorTelefono.value = ''
}

const validarCorreo = (email) => {
  // Validación básica (no intenta cubrir todos los casos RFC).
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  return re.test(email)
}

const validarTelefono = (phone) => {
  // Teléfono ES típico: 9 dígitos (se limpia a solo números en el input).
  const re = /^[0-9]{9}$/

  return re.test(phone)
}

const guardarInfoPersonal = async () => {
  errorCorreo.value = ''
  errorTelefono.value = ''

  if (!validarCorreo(form.email)) {
    errorCorreo.value = 'Email no válido'

    return
  }

  if (form.phone && !validarTelefono(form.phone)) {
    errorTelefono.value = 'Teléfono debe tener 9 dígitos'

    return
  }

  try {
    // Actualiza los datos en backend y sincroniza el store local.
    await axios.put('/api/user/profile', {
      email: form.email,
      phone: form.phone
    })

    if (authStore.usuario) {
      authStore.usuario.email = form.email
      authStore.usuario.phone = form.phone
    }

    editandoPersonal.value = false
    mensajeInfo.value = 'Información actualizada correctamente'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (error) {
    mensajeError.value = 'Error al actualizar la información: ' + (error.response?.data?.message || 'Error desconocido')
    setTimeout(() => { mensajeError.value = '' }, 4000)
  }
}

const cambiarContrasena = async () => {
  if (!puedeCambiarContrasena.value) return
  try {
    // El backend decide la política final; aquí solo prevenimos errores obvios.
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

const eliminarCuenta = async () => {
  try {
    // Solicita eliminación en backend. Aunque falle, cerramos sesión para seguridad.
    await axios.delete('/api/user')
  } catch (error) {
  }

  await authStore.cerrarSesion()
  router.visit('/')
}

onMounted(async () => {
  // Aseguramos tener el perfil cargado (p.ej. tras recargar la página).
  if (!perfil.value) {
    await authStore.verificarAutenticacion()
  }
})

watch(
  () => perfil.value?.avatar,
  (avatar) => {
    // Mantiene el avatar mostrado alineado con el estado global.
    avatarUsuario.value = normalizarUrlAvatar(avatar)
  },
  { immediate: true }
)
</script>
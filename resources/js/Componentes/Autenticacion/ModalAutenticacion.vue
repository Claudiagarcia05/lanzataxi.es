<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>

      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-8 transform transition-all" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" aria-describedby="auth-modal-desc" tabindex="-1">
          <button type="button" @click="closeModal" class="absolute top-4 right-4 text-neutral-slate hover:text-neutral-dark" aria-label="Cerrar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <div class="text-center mb-8">
            <img src="/images/logo.png" alt="LanzaTaxi" class="h-20 mx-auto mb-2" cargando="lazy" decoding="async">
            <h2 id="auth-modal-title" class="text-2xl font-bold text-lanzarote-blue">LanzaTaxi</h2>
            <p id="auth-modal-desc" class="text-neutral-slate text-sm mt-1">
              {{ isLogin ? 'Bienvenido de nuevo' : 'Crea tu cuenta' }}
            </p>
          </div>

          <div v-if="error" :class="[isLogin && error === 'Usuario creado correctamente. Inicie sesión.' ? 'mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-600 text-sm' : 'mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm']" aria-live="polite">
            {{ error }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div v-if="!isLogin">
              <label class="block text-sm font-medium text-neutral-dark mb-1">Nombre completo</label>
              <input v-model="formData.name" type="text" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="Tu nombre">
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-1">Email</label>
              <input v-model="formData.email" type="email" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="tucorreo@ejemplo.com">
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-1">Contraseña</label>
              <input v-model="formData.password" type="password" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="••••••••">
            </div>

            <div v-if="!isLogin">
              <label class="block text-sm font-medium text-neutral-dark mb-1">Confirmar contraseña</label>
              <input v-model="formData.password_confirmation" type="password" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="••••••••">
            </div>

            <div v-if="!isLogin">
              <label class="block text-sm font-medium text-neutral-dark mb-2">Tipo de usuario</label>
              <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="formData.role = 'pasajero'" :class="['p-2 rounded-lg border text-sm transition-all', formData.role === 'pasajero' ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic text-neutral-dark hover:border-lanzarote-blue']">
                  Pasajero
                </button>
                <button type="button" @click="formData.role = 'conductor'" :class="[ 'p-2 rounded-lg border text-sm transition-all', formData.role === 'conductor' ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic text-neutral-dark hover:border-lanzarote-blue']">
                  Taxista
                </button>
                <button type="button" @click="formData.role = 'admin'" :class="[ 'p-2 rounded-lg border text-sm transition-all', formData.role === 'admin' ? 'bg-lanzarote-blue text-white border-lanzarote-blue' : 'border-neutral-volcanic text-neutral-dark hover:border-lanzarote-blue']">
                  Admin
                </button>
              </div>
            </div>

            <button type="submit" :disabled="cargando" class="w-full bg-lanzarote-blue text-white py-3 px-4 rounded-lg font-semibold hover:bg-lanzarote-yellow hover:text-black transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-6" :aria-busy="cargando ? 'true' : 'false'">
              <span v-if="!cargando">{{ isLogin ? 'Iniciar sesión' : 'Crear cuenta' }}</span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Procesando...
              </span>
            </button>

            <p class="text-center text-sm text-neutral-slate mt-4">
              {{ isLogin ? '¿No tienes cuenta?' : '¿Ya tienes cuenta?' }}
              <button type="button" @click="isLogin = !isLogin; error = ''" class="text-lanzarote-blue font-semibold hover:underline ml-1">
                {{ isLogin ? 'Regístrate' : 'Inicia sesión' }}
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

const props = defineProps({
  modelValue: Boolean
})

const emit = defineEmits(['update:modelValue'])

const isLogin = ref(true)
const cargando = ref(false)
const error = ref('')

const formData = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'pasajero'
})

const closeModal = () => {
  emit('update:modelValue', false)
  resetForm()
}

const resetForm = () => {
  formData.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'pasajero'
  }
  error.value = ''
}

// Validar formulario antes de enviar
const validateForm = () => {
  if (isLogin.value) {
    if (!formData.value.email.trim() || !formData.value.password) {
      error.value = 'Email y contraseña son requeridos'

      return false
    }
  } else {
    if (!formData.value.name.trim()) {
      error.value = 'El nombre es requerido'

      return false
    }
    if (!formData.value.email.trim()) {
      error.value = 'El email es requerido'

      return false
    }
    if (!formData.value.password) {
      error.value = 'La contraseña es requerida'

      return false
    }
    if (!formData.value.password_confirmation) {
      error.value = 'Debes confirmar la contraseña'

      return false
    }
    if (formData.value.password !== formData.value.password_confirmation) {
      error.value = 'Las contraseñas no coinciden'

      return false
    }
    if (formData.value.password.length < 6) {
      error.value = 'La contraseña debe tener al menos 6 caracteres'

      return false
    }

    // Regla de negocio: coherencia entre el tipo de usuario Admin y el dominio del email
    // - Admin => email obligatorio @admin.com
    // - Email @admin.com => rol obligatorio Admin
    const email = (formData.value.email || '').trim().toLowerCase()
    const role = formData.value.role || 'pasajero'
    const isAdminEmail = email.endsWith('@admin.com')

    const mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.'

    if (role === 'admin' && !isAdminEmail) {
      error.value = mensajeGenericoCredenciales

      return false
    }

    if (role !== 'admin' && isAdminEmail) {
      error.value = mensajeGenericoCredenciales

      return false
    }
  }

  return true
}

const handleSubmit = async () => {
  if (!validateForm()) {

    return
  }

  cargando.value = true
  error.value = ''

  formData.value.email = formData.value.email.trim()
  formData.value.name = formData.value.name.trim()

  try {
    if (isLogin.value) {
      const respuesta = await axios.post('/api/login', {
        email: formData.value.email,
        password: formData.value.password,
      })

      const token = respuesta.data?.token
      const usuario = respuesta.data?.user

      if (!token) {
        error.value = 'No se pudo iniciar sesión. Inténtalo de nuevo.'

        return
      }

      localStorage.setItem('token', token)
      if (usuario) {
        localStorage.setItem('usuario', JSON.stringify(usuario))
      }
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // Hacer login en web session
      window.location.href = `/auth/session-login?token=${encodeURIComponent(token)}`

      return
    }

    const respuesta = await axios.post('/api/register', {
      name: formData.value.name,
      email: formData.value.email,
      password: formData.value.password,
      password_confirmation: formData.value.password_confirmation,
      role: formData.value.role,
      phone: formData.value.phone?.trim() || null,
    })

    // Si el backend responde correctamente, mostrar mensaje de éxito y cambiar a login
    if (!respuesta.data?.user) {
      error.value = 'No se pudo crear la cuenta. Inténtalo de nuevo.'
      
      return
    }

    // Mostrar mensaje verde y cambiar a login
    error.value = ''
    isLogin.value = true
    formData.value = {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: 'pasajero'
    }
    // Mensaje de éxito permanente
    error.value = 'Usuario creado correctamente. Inicie sesión.'
  } catch (e) {
    const mensaje = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})?.flat()?.[0]
      || 'Error en la autenticación'

    // Unificar mensaje para la regla Admin ⇔ @admin.com
    const mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.'
    const mensajesAUnificar = [
      'Para crear un usuario administrador el email debe terminar en @admin.com.',
      'Si el email termina en @admin.com debes seleccionar el tipo de usuario Admin.'
    ]

    error.value = mensajesAUnificar.includes(mensaje)
      ? mensajeGenericoCredenciales
      : mensaje
  } finally {
    cargando.value = false
  }
}
</script>
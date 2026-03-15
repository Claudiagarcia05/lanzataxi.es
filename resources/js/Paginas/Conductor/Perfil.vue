<template>
  <DisposicionConductor>
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl shadow-sm p-8">
        <h1 class="text-2xl font-bold text-neutral-dark mb-6">Mi Perfil</h1>
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
                {{ perfil?.name?.charAt(0) || 'C' }}
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
            <h3 class="font-semibold text-neutral-dark">Información personal</h3>
            <button v-if="!editandoPersonal" @click="iniciarEdicionPersonal" class="text-sm text-lanzarote-blue flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              <span>Editar información</span>
            </button>
          </div>
          <div v-if="!editandoPersonal" class="space-y-3">
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">Nombre:</span>
              <span class="text-neutral-dark font-medium">{{ perfil?.name }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">Email:</span>
              <span class="text-neutral-dark">{{ perfil?.email }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-sm text-neutral-slate">Teléfono:</span>
              <span class="text-neutral-dark">{{ perfil?.phone || 'No especificado' }}</span>
            </div>
            <button @click="toggleActividadLaboral" :class=" ['px-4 py-2 rounded-lg font-bold transition-colors mt-2', estaEnLinea ? 'bg-green-500 text-white' : 'bg-red-500 text-white hover:bg-red-600']">
              {{ estaEnLinea ? 'Activado' : 'Desconectado' }}
            </button>
          </div>
          <div v-if="editandoPersonal" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Nombre</label>
              <input :value="perfil?.name || ''" type="text" disabled class="w-full px-4 py-2 bg-neutral-soft border border-neutral-volcanic rounded-lg cursor-not-allowed">
              <p class="text-xs text-neutral-slate mt-1">El nombre no se puede modificar</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Email</label>
              <input v-model="form.email" type="email" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': errorCorreo }">
              <p v-if="errorCorreo" class="text-xs text-red-500 mt-1">{{ errorCorreo }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Teléfono</label>
              <input v-model="form.phone" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="9" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: 628123456" :class="{ 'border-red-500': errorTelefono }" @input="form.phone = form.phone.replace(/\D/g, '').slice(0, 9)">
              <p v-if="errorTelefono" class="text-xs text-red-500 mt-1">{{ errorTelefono }}</p>
            </div>
            <div class="flex space-x-3 pt-2">
              <button @click="guardarInfoPersonal" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black">
                Guardar cambios
              </button>
              <button @click="cancelarEdicionPersonal" class="px-4 py-2 border border-neutral-volcanic rounded-lg hover:bg-neutral-soft">
                Cancelar
              </button>
            </div>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">Cambiar contraseña</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Nueva contraseña</label>
              <input v-model="contrasena.nueva" type="password" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': contrasena.nueva && !esContrasenaFuerte }" @input="comprobarFuerzaContrasena">
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
                  ✓ Mínimo 8 caracteres
                </p>
                <p class="text-xs" :class="/[A-Z]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos una mayúscula
                </p>
                <p class="text-xs" :class="/[a-z]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos una minúscula
                </p>
                <p class="text-xs" :class="/[0-9]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos un número
                </p>
                <p class="text-xs" :class="/[!@#$%^&*]/.test(contrasena.nueva) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos un carácter especial (!@#$%^&*)
                </p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Confirmar nueva contraseña</label>
              <input v-model="contrasena.confirmacion" type="password" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': contrasena.confirmacion && contrasena.nueva !== contrasena.confirmacion }">
              <p v-if="contrasena.confirmacion && contrasena.nueva !== contrasena.confirmacion" class="text-xs text-red-500 mt-1">
                Las contraseñas no coinciden
              </p>
            </div>
            <button @click="cambiarContrasena" :disabled="!puedeCambiarContrasena" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black disabled:opacity-50 disabled:cursor-not-allowed">
              Actualizar contraseña
            </button>
          </div>
        </div>

        <div class="pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">Preferencias y notificaciones</h3>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.notificacionesCorreo" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">Recibir notificaciones por email</span>
            </label>
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferencias.notificacionesSms" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">Recibir notificaciones por SMS</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </DisposicionConductor>
</template>


<script setup>
import { ref, reactive, computed, watch } from 'vue'
import DisposicionConductor from '../../Disposiciones/DisposicionConductor.vue'
import { useConductorStore } from '../../Almacenes/almacenConductor'
import { storeToRefs } from 'pinia'
import axios from 'axios'

const conductorStore = useConductorStore()
const { estaEnLinea, perfil } = storeToRefs(conductorStore)
const mensajeEstadoLaboral = ref('')
const mensajeError = ref('')
const mensajeInfo = ref('')
const vistaPreviaAvatar = ref(null)
const avatarUsuario = ref(perfil.value?.avatar ? `/storage/${perfil.value.avatar}` : null)
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

  return contrasena.nueva?.length >= 8 &&
         /[A-Z]/.test(contrasena.nueva) &&
         /[a-z]/.test(contrasena.nueva) &&
         /[0-9]/.test(contrasena.nueva) &&
         /[!@#$%^&*]/.test(contrasena.nueva)
})
const puedeCambiarContrasena = computed(() => {

  return esContrasenaFuerte.value &&
         contrasena.nueva === contrasena.confirmacion
})
const comprobarFuerzaContrasena = () => {
  let fuerza = 0
  if (contrasena.nueva?.length >= 8) fuerza++
  if (/[A-Z]/.test(contrasena.nueva)) fuerza++
  if (/[a-z]/.test(contrasena.nueva)) fuerza++
  if (/[0-9]/.test(contrasena.nueva)) fuerza++
  if (/[!@#$%^&*]/.test(contrasena.nueva)) fuerza++
  fuerzaContrasena.value = fuerza
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
  if (!contrasena.nueva) return ''
  if (fuerzaContrasena.value <= 2) return 'Contraseña débil'
  if (fuerzaContrasena.value <= 3) return 'Contraseña media'

  return 'Contraseña fuerte'
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
  if (!archivo) return
  try {
    const datosFormulario = new FormData()
    datosFormulario.append('avatar', archivo)
    const response = await axios.post('/api/user/avatar', datosFormulario, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    const avatarUrl = `/storage/${response.data.avatar}`
    avatarUsuario.value = avatarUrl
    if (perfil.value) {
      perfil.value.avatar = avatarUrl
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
const manejarErrorImagen = (event) => {
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
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  return re.test(email)
}
const validarTelefono = (phone) => {
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
    await axios.put('/api/user/profile', {
      email: form.email,
      phone: form.phone
    })
    if (perfil.value) {
      perfil.value.email = form.email
      perfil.value.phone = form.phone
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
const toggleActividadLaboral = async () => {
  mensajeEstadoLaboral.value = ''
  try {
    await conductorStore.cambiarEstadoEnLinea()
    mensajeEstadoLaboral.value = estaEnLinea.value
      ? '¡Ahora estás activado y en línea!'
      : 'Has pasado a desconectado.'
  } catch (error) {
    mensajeEstadoLaboral.value = 'Error al cambiar el estado laboral.'
  }
}
if (!perfil.value) {
  conductorStore.obtenerPerfilConductor()
}

watch(
  () => perfil.value?.avatar,
  (avatar) => {
    avatarUsuario.value = avatar ? `/storage/${avatar}` : null
  },
  { immediate: true }
)
</script>
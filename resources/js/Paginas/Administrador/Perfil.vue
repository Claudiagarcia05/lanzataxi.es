<template>
  <DisposicionAdministrador>
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl shadow-sm p-8">
        <h1 class="text-2xl font-bold text-neutral-dark mb-6">Mi Perfil</h1>
        <div v-if="errorMsg" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-red-500">{{ errorMsg }}</p>
        </div>
        <div v-if="infoMsg" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
          <p class="text-sm font-medium text-green-500">{{ infoMsg }}</p>
        </div>

        <div class="flex justify-center mb-8">
          <div class="relative">
            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-lanzarote-blue/20">
              <img v-if="avatarPreview || userAvatar" :src="avatarPreview || userAvatar" :alt="perfil?.name" class="w-full h-full object-cover" @error="handleImageError" key="avatar-image">
              <div v-else class="w-full h-full bg-lanzarote-blue text-white flex items-center justify-center text-4xl font-bold">
                {{ perfil?.name?.charAt(0) || 'A' }}
              </div>
            </div>
            <label class="absolute bottom-0 right-0 bg-lanzarote-blue rounded-full p-2 shadow-lg cursor-pointer hover:bg-lanzarote-yellow transition-colors">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="hidden" accept="image/*" @change="handleAvatarUpload">
            </label>
          </div>
        </div>

        <div class="border-b border-neutral-volcanic pb-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-neutral-dark">Información personal</h3>
            <button v-if="!editingPersonal" @click="startEditingPersonal" class="text-sm text-lanzarote-blue flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              <span>Editar información</span>
            </button>
          </div>
          <div v-if="!editingPersonal" class="space-y-3">
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
          </div>
          <div v-if="editingPersonal" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Nombre</label>
              <input :value="perfil?.name || ''" type="text" disabled class="w-full px-4 py-2 bg-neutral-soft border border-neutral-volcanic rounded-lg cursor-not-allowed">
              <p class="text-xs text-neutral-slate mt-1">El nombre no se puede modificar</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Email</label>
              <input v-model="form.email" type="email" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': emailError }">
              <p v-if="emailError" class="text-xs text-red-500 mt-1">{{ emailError }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Teléfono</label>
              <input v-model="form.phone" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="9" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" placeholder="Ej: 628123456" :class="{ 'border-red-500': phoneError }" @input="form.phone = form.phone.replace(/\D/g, '').slice(0, 9)">
              <p v-if="phoneError" class="text-xs text-red-500 mt-1">{{ phoneError }}</p>
            </div>
            <div class="flex space-x-3 pt-2">
              <button @click="savePersonalInfo" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black">
                Guardar cambios
              </button>
              <button @click="cancelEditingPersonal" class="px-4 py-2 border border-neutral-volcanic rounded-lg hover:bg-neutral-soft">
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
              <input v-model="password.new" type="password" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': password.new && !isPasswordStrong }" @input="checkPasswordStrength">
              <div v-if="password.new" class="mt-2">
                <div class="flex space-x-1 h-1 mb-2">
                  <div class="flex-1 h-full rounded" :class="strengthColor(1)"></div>
                  <div class="flex-1 h-full rounded" :class="strengthColor(2)"></div>
                  <div class="flex-1 h-full rounded" :class="strengthColor(3)"></div>
                  <div class="flex-1 h-full rounded" :class="strengthColor(4)"></div>
                </div>
                <p class="text-xs" :class="strengthTextColor">{{ strengthMessage }}</p>
              </div>
              <div class="mt-2 space-y-1">
                <p class="text-xs" :class="password.new?.length >= 8 ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Mínimo 8 caracteres
                </p>
                <p class="text-xs" :class="/[A-Z]/.test(password.new) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos una mayúscula
                </p>
                <p class="text-xs" :class="/[a-z]/.test(password.new) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos una minúscula
                </p>
                <p class="text-xs" :class="/[0-9]/.test(password.new) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos un número
                </p>
                <p class="text-xs" :class="/[!@#$%^&*]/.test(password.new) ? 'text-success-jable' : 'text-neutral-slate'">
                  ✓ Al menos un carácter especial (!@#$%^&*)
                </p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-dark mb-2">Confirmar nueva contraseña</label>
              <input v-model="password.confirm" type="password" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue" :class="{ 'border-red-500': password.confirm && password.new !== password.confirm }">
              <p v-if="password.confirm && password.new !== password.confirm" class="text-xs text-red-500 mt-1">
                Las contraseñas no coinciden
              </p>
            </div>
            <button @click="changePassword" :disabled="!canChangePassword" class="px-4 py-2 bg-lanzarote-blue text-white rounded-lg hover:bg-lanzarote-yellow hover:text-black disabled:opacity-50 disabled:cursor-not-allowed">
              Actualizar contraseña
            </button>
          </div>
        </div>

        <div class="pb-6 mb-6">
          <h3 class="font-semibold text-neutral-dark mb-4">Preferencias y notificaciones</h3>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferences.email_notifications" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">Recibir notificaciones por email</span>
            </label>
            <label class="flex items-center space-x-3">
              <input type="checkbox" v-model="preferences.sms_notifications" class="w-4 h-4 text-lanzarote-blue">
              <span class="text-neutral-dark">Recibir notificaciones por SMS</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button @click="showDeleteConfirm = true" class="text-red-600 hover:text-red-800 text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Eliminar mi cuenta</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-neutral-dark mb-4">¿Eliminar cuenta?</h3>
        <p class="text-neutral-slate mb-6">Esta acción es permanente y no se puede deshacer. Se eliminarán todos tus datos y viajes.</p>
        <div class="flex space-x-3">
          <button @click="deleteAccount" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
            Sí, eliminar
          </button>
          <button @click="showDeleteConfirm = false" class="flex-1 border border-neutral-volcanic py-2 rounded-lg hover:bg-neutral-soft">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </DisposicionAdministrador>
</template>


<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import DisposicionAdministrador from '../../Disposiciones/DisposicionAdministrador.vue'
import { useAuthStore } from '../../Almacenes/almacenAutenticacion.js'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const authStore = useAuthStore()
const perfil = computed(() => authStore.usuario)

const errorMsg = ref('')
const infoMsg = ref('')
const avatarPreview = ref(null)
const showDeleteConfirm = ref(false)

const normalizeAvatarUrl = (avatar) => {
  if (typeof avatar !== 'string') return null

  const trimmed = avatar.trim()
  if (!trimmed) return null

  if (/^https?:\/\//i.test(trimmed)) return trimmed
  if (trimmed.startsWith('/storage/')) return trimmed
  if (trimmed.startsWith('storage/')) return `/${trimmed}`
  if (trimmed.startsWith('/avatars/')) return `/storage${trimmed}`
  if (!trimmed.startsWith('/')) return `/storage/${trimmed}`

  return trimmed
}

const userAvatar = ref(normalizeAvatarUrl(perfil.value?.avatar))
const editingPersonal = ref(false)
const emailError = ref('')
const phoneError = ref('')

const form = reactive({
  email: '',
  phone: ''
})

const password = reactive({
  new: '',
  confirm: ''
})

const passwordStrength = ref(0)

const preferences = reactive({
  email_notifications: true,
  sms_notifications: false
})

const isPasswordStrong = computed(() => {
  return password.new?.length >= 8 &&
         /[A-Z]/.test(password.new) &&
         /[a-z]/.test(password.new) &&
         /[0-9]/.test(password.new) &&
         /[!@#$%^&*]/.test(password.new)
})

const canChangePassword = computed(() => {
  return isPasswordStrong.value &&
         password.new === password.confirm
})

const checkPasswordStrength = () => {
  let strength = 0
  if (password.new?.length >= 8) strength++
  if (/[A-Z]/.test(password.new)) strength++
  if (/[a-z]/.test(password.new)) strength++
  if (/[0-9]/.test(password.new)) strength++
  if (/[!@#$%^&*]/.test(password.new)) strength++
  passwordStrength.value = strength
}

const strengthColor = (level) => {
  if (!password.new) return 'bg-neutral-volcanic'
  if (passwordStrength.value >= level) {
    if (passwordStrength.value <= 2) return 'bg-red-500'
    if (passwordStrength.value <= 3) return 'bg-yellow-500'

    return 'bg-success-jable'
  }

  return 'bg-neutral-volcanic'
}

const strengthMessage = computed(() => {
  if (!password.new) return ''
  if (passwordStrength.value <= 2) return 'Contraseña débil'
  if (passwordStrength.value <= 3) return 'Contraseña media'

  return 'Contraseña fuerte'
})

const strengthTextColor = computed(() => {
  if (!password.new) return ''
  if (passwordStrength.value <= 2) return 'text-red-500'
  if (passwordStrength.value <= 3) return 'text-yellow-600'

  return 'text-success-jable'
})

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      errorMsg.value = 'La imagen no puede superar los 2MB';
      setTimeout(() => { errorMsg.value = ''; }, 4000);

      return
    }
    if (!file.type.startsWith('image/')) {
      errorMsg.value = 'Solo se permiten imágenes';
      setTimeout(() => { errorMsg.value = ''; }, 4000);

      return
    }
    const reader = new FileReader()
    reader.onload = async (e) => {
      avatarPreview.value = e.target.result
      await saveAvatar(file)
    }
    reader.readAsDataURL(file)
  }
}

const saveAvatar = async (file) => {
  if (!file) return
  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const response = await axios.post('/api/user/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    const avatarUrl = normalizeAvatarUrl(response.data.avatar)
    userAvatar.value = avatarUrl

    if (authStore.usuario) {
      authStore.usuario.avatar = avatarUrl
    }

    setTimeout(() => {
      avatarPreview.value = null
    }, 500)
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Error al subir la imagen';
    setTimeout(() => { errorMsg.value = ''; }, 4000);
    avatarPreview.value = null
  }
}

const handleImageError = () => {
  userAvatar.value = null
  avatarPreview.value = null
}

const startEditingPersonal = () => {
  form.email = perfil.value?.email || ''
  form.phone = perfil.value?.phone || ''
  editingPersonal.value = true
}

const cancelEditingPersonal = () => {
  editingPersonal.value = false
  emailError.value = ''
  phoneError.value = ''
}

const validateEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  return re.test(email)
}

const validatePhone = (phone) => {
  const re = /^[0-9]{9}$/

  return re.test(phone)
}

const savePersonalInfo = async () => {
  emailError.value = ''
  phoneError.value = ''

  if (!validateEmail(form.email)) {
    emailError.value = 'Email no válido'

    return
  }

  if (form.phone && !validatePhone(form.phone)) {
    phoneError.value = 'Teléfono debe tener 9 dígitos'

    return
  }

  try {
    await axios.put('/api/user/profile', {
      email: form.email,
      phone: form.phone
    })

    if (authStore.usuario) {
      authStore.usuario.email = form.email
      authStore.usuario.phone = form.phone
    }

    editingPersonal.value = false
    infoMsg.value = 'Información actualizada correctamente';
    setTimeout(() => { infoMsg.value = ''; }, 4000);
  } catch (error) {
    errorMsg.value = 'Error al actualizar la información: ' + (error.response?.data?.message || 'Error desconocido');
    setTimeout(() => { errorMsg.value = ''; }, 4000);
  }
}

const changePassword = async () => {
  if (!canChangePassword.value) return
  try {
    await axios.put('/api/user/password', {
      new_password: password.new,
      new_password_confirmation: password.confirm
    })
    password.new = ''
    password.confirm = ''
    infoMsg.value = 'Contraseña actualizada correctamente';
    setTimeout(() => { infoMsg.value = ''; }, 4000);
  } catch (error) {
    errorMsg.value = 'Error al actualizar la contraseña: ' + (error.response?.data?.message || 'Error desconocido');
    setTimeout(() => { errorMsg.value = ''; }, 4000);
  }
}

const deleteAccount = async () => {
  try {
    await axios.delete('/api/user')
  } catch (error) {
  }

  await authStore.logout()
  router.visit('/')
}

onMounted(async () => {
  if (!perfil.value) {
    await authStore.checkAuth()
  }
})

watch(
  () => perfil.value?.avatar,
  (avatar) => {
    userAvatar.value = normalizeAvatarUrl(avatar)
  },
  { immediate: true }
)
</script>
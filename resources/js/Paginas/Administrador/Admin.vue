<template>
  <DisposicionAdministrador>
    <div class="bg-gradient-to-r from-lanzarote-blue to-blue-800 rounded-2xl p-8 mb-8 text-white">
      <h1 class="text-3xl font-bold mb-2">Administradores</h1>
      <p class="text-blue-100">Alta de nuevos administradores</p>
    </div>

    <div v-if="mensajeError" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-red-500">{{ mensajeError }}</p>
    </div>
    <div v-if="mensajeInfo" class="mb-6 bg-green-50 border border-green-200 p-4 rounded-lg">
      <p class="text-sm font-medium text-green-500">{{ mensajeInfo }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
      <div class="p-6 border-b border-neutral-volcanic">
        <h3 class="font-semibold text-neutral-dark">Crear administrador</h3>
        <p class="text-sm text-neutral-slate mt-1">El email debe terminar en @admin.es</p>
      </div>

      <form @submit.prevent="crear" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-neutral-dark mb-1">Nombre</label>
          <input v-model="form.name" type="text" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="Nombre y apellidos">
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-dark mb-1">Email</label>
          <input v-model="form.email" type="email" required class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="nuevo@admin.es">
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-dark mb-1">Teléfono (opcional)</label>
          <input v-model="form.phone" type="text" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="+34 ...">
        </div>

        <div></div>

        <div>
          <label class="block text-sm font-medium text-neutral-dark mb-1">Contraseña</label>
          <input v-model="form.password" type="password" required minlength="6" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="••••••••">
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-dark mb-1">Confirmar contraseña</label>
          <input v-model="form.password_confirmation" type="password" required minlength="6" class="w-full px-4 py-2 border border-neutral-volcanic rounded-lg focus:ring-2 focus:ring-lanzarote-blue focus:border-transparent" placeholder="••••••••">
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
          <button type="submit" :disabled="cargando" class="bg-lanzarote-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-lanzarote-yellow hover:text-black disabled:opacity-50">
            {{ cargando ? 'Creando...' : 'Crear admin' }}
          </button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm mt-6">
      <div class="p-6 border-b border-neutral-volcanic">
        <h3 class="font-semibold text-neutral-dark">Administradores</h3>
        <p class="text-sm text-neutral-slate mt-1">Listado rápido (nombre, email y estado)</p>
      </div>

      <div class="p-6">
        <div class="max-h-56 overflow-y-auto border border-neutral-volcanic rounded-lg">
          <div
            v-for="a in administradores"
            :key="a.id"
            class="flex items-center justify-between gap-3 px-4 py-3 border-b border-neutral-volcanic last:border-b-0"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-neutral-dark truncate">{{ a.name }}</p>
              <p class="text-xs text-neutral-slate truncate">{{ a.email }}</p>
            </div>
            <span :class="['shrink-0 px-2 py-1 rounded-full text-xs', a.is_disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
              {{ a.is_disabled ? 'De baja' : 'Activo' }}
            </span>
          </div>

          <div v-if="administradores.length === 0" class="px-4 py-4 text-sm text-neutral-slate">
            No hay administradores.
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

const adminStore = useAdminStore()

const cargando = ref(false)
const mensajeError = ref('')
const mensajeInfo = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  try {
    await adminStore.obtenerUsuarios()
  } catch (_) {
    // Silencioso: si falla la carga, el alta sigue funcionando.
  }
})

const administradores = computed(() => adminStore.usuarios.filter(u => u.role === 'admin'))

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  }
}

const crear = async () => {
  mensajeError.value = ''
  mensajeInfo.value = ''

  const correo = (form.value.email || '').trim().toLowerCase()
  if (!correo.endsWith('@admin.es')) {
    mensajeError.value = 'El email del administrador debe terminar en @admin.es.'
    return
  }

  if (!form.value.password || form.value.password.length < 6) {
    mensajeError.value = 'La contraseña debe tener al menos 6 caracteres.'
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    mensajeError.value = 'Las contraseñas no coinciden.'
    return
  }

  cargando.value = true
  try {
    await adminStore.crearAdmin({
      name: form.value.name?.trim(),
      email: form.value.email?.trim(),
      phone: form.value.phone?.trim() || null,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })

    resetForm()
    mensajeInfo.value = 'Administrador creado correctamente.'
    setTimeout(() => { mensajeInfo.value = '' }, 4000)
  } catch (e) {
    const msg = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})?.flat()?.[0]
      || 'No se pudo crear el administrador.'

    mensajeError.value = msg
    setTimeout(() => { mensajeError.value = '' }, 5000)
  } finally {
    cargando.value = false
  }
}
</script>

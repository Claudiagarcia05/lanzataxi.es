<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <!--
      Página de autenticación (registro).
      Igual que Login.vue, esta vista sólo aloja el ModalAutenticacion y lo centra.
      La lógica del registro (campos, validaciones, llamadas a API, errores, etc.)
      pertenece al componente del modal.
    -->
    <ModalAutenticacion v-model="modalVisible" />
  </div>
</template>


<script setup>
import { ref, watch } from 'vue'
import { router as inertiaRouter } from '@inertiajs/vue3'
import ModalAutenticacion from '../../Componentes/Autenticacion/ModalAutenticacion.vue'

// Abrimos el modal inmediatamente al entrar en la ruta de registro.
const modalVisible = ref(true)

watch(modalVisible, (estaAbierto) => {
  // Si el modal se cierra, salimos de la pantalla de registro y volvemos al inicio.
  // Inertia gestiona la navegación sin recargar toda la página.
  if (!estaAbierto) inertiaRouter.visit('/')
})
</script>
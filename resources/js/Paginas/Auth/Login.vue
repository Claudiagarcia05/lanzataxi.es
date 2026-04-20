<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <!--
      Página de autenticación (login).
      Esta vista actúa como contenedor: el flujo real (formularios, validaciones y envío)
      vive dentro del componente ModalAutenticacion.

      El wrapper sólo centra el modal a pantalla completa.
    -->
    <ModalAutenticacion v-model="modalVisible" />
  </div>
</template>


<script setup>
import { ref, watch } from 'vue'
import { router as inertiaRouter } from '@inertiajs/vue3'
import ModalAutenticacion from '../../Componentes/Autenticacion/ModalAutenticacion.vue'

// El modal debe mostrarse abierto al entrar en la ruta.
const modalVisible = ref(true)

watch(modalVisible, (estaAbierto) => {
  // Si el usuario cierra el modal (cancelar, X, clic fuera, etc.), volvemos al inicio.
  // Usamos el router de Inertia para mantener navegación SPA (sin recarga completa).
  if (!estaAbierto) inertiaRouter.visit('/')
})
</script>
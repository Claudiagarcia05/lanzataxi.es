<template>
  <!-- Página de registro: se limita a mostrar el modal de autenticación centrado -->
  <div class="min-h-screen flex items-center justify-center px-4">
    <!-- El modal controla la UI de registro; aquí solo gestionamos apertura/cierre -->
    <ModalAutenticacion v-model="show" />
  </div>
</template>


<script setup>
// Esta vista existe para integrarse con las rutas web (/register) y abrir el modal.
// Cuando el modal se cierra, redirigimos al inicio.
import { ref, watch } from 'vue'
import { router as inertiaRouter } from '@inertiajs/vue3'
import ModalAutenticacion from '../../Componentes/Autenticacion/ModalAutenticacion.vue'

// El modal inicia abierto al entrar en la ruta
const show = ref(true)

watch(show, (isOpen) => {
  // Si el usuario cierra el modal, volver a la landing
  if (!isOpen) inertiaRouter.visit('/')
})
</script>
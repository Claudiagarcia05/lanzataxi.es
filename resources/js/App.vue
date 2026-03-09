<template>
  <!-- Componente raíz de la SPA:
       aquí se renderiza la vista correspondiente según la ruta actual -->
  <RouterView />
</template>


<script setup>
// Importa el contenedor de rutas de Vue Router
import { RouterView } from 'vue-router';
</script>
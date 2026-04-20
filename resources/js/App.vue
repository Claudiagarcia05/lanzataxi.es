<template>
  <!-- Componente raíz de Vue Router.
       Inertia monta su propio "App"; este archivo sirve como raíz cuando se usa Vue Router.
       Mantenerlo minimal evita lógica duplicada en la capa superior. -->
  <RouterView />
</template>


<script setup>
// RouterView renderiza la ruta activa (páginas/hijos). No se define estado aquí a propósito.
import { RouterView } from 'vue-router';
</script>
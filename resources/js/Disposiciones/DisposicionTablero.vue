<template>
  <DisposicionAdministrador>
    <slot />
  </DisposicionAdministrador>
</template>


<script setup>
import DisposicionAdministrador from './DisposicionAdministrador.vue'

/**
 * Disposición “Tablero”.
 *
 * Actualmente actúa como alias/wrapper de `DisposicionAdministrador` para
 * reutilizar el mismo layout del dashboard. Se mantiene por claridad y/o
 * compatibilidad con importaciones antiguas.
 */
</script>
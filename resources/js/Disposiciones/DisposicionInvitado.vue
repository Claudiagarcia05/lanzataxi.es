<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/">
                <LogoAplicacion class="h-20 w-20 fill-current text-gray-500"/>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>


<script setup>
import LogoAplicacion from '../Componentes/LogoAplicacion.vue';
import { Link } from '@inertiajs/vue3';

/**
 * Disposición (layout) para páginas de invitado.
 *
 * Se usa en pantallas públicas como login/registro/recuperación:
 * - Centra el contenido en una tarjeta
 * - Muestra logo y enlace a inicio
 * - Expone un slot para el formulario
 */
</script>
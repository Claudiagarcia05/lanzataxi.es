<template>
  <!-- Barra de navegación principal, fija en la parte superior -->
  <nav class="bg-white shadow-sm fixed w-full top-0 z-50" aria-label="Principal">
    <!-- Enlace para accesibilidad: saltar al contenido principal -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-white focus:text-neutral-dark focus:px-4 focus:py-2 focus:rounded-lg">
      {{ t('nav.skipToContent') }}
    </a>
    <!-- Contenedor principal de la barra -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Sección superior: logo y menú de navegación -->
      <div class="flex justify-between items-center h-20">
        <!-- Botón del logo, vuelve al inicio -->
        <button type="button" @click="goToHome" class="flex items-center space-x-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-lanzarote-blue">
          <img src="/images/logo.png" alt="LanzaTaxi" class="h-20 w-auto" cargando="lazy" decoding="async">
        </button>

        <!-- Menú de navegación para escritorio -->
        <div class="hidden md:flex items-center space-x-8">
          <button type="button" @click="scrollToPasos" class="text-neutral-dark hover:text-lanzarote-blue transition-colors font-medium">
            {{ t('nav.howItWorks') }}
          </button>
          <button type="button" @click="scrollToMunicipios" class="text-neutral-dark hover:text-lanzarote-blue transition-colors font-medium">
            {{ t('nav.municipalities') }}
          </button>
          <button type="button" @click="scrollToTestimonios" class="text-neutral-dark hover:text-lanzarote-blue transition-colors font-medium">
            {{ t('nav.reviews') }}
          </button>
          <button type="button" @click="scrollToFooter" class="text-neutral-dark hover:text-lanzarote-blue transition-colors font-medium">
            {{ t('nav.contact') }}
          </button>

          <label class="sr-only" for="selector-idioma-desktop">{{ t('nav.language') }}</label>
          <select
            id="selector-idioma-desktop"
            v-model="localeSeleccionado"
            @change="cambiarIdioma"
            class="border border-neutral-volcanic rounded-lg px-3 py-2 text-sm text-neutral-dark bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-lanzarote-blue"
            aria-label="Selector de idioma"
          >
            <option value="es">ES</option>
            <option value="en">EN</option>
          </select>

          <button type="button" @click="showAuthModal = true" class="bg-lanzarote-blue text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-lanzarote-yellow hover:text-black transition-all shadow-md">
            {{ t('nav.login') }}
          </button>
        </div>

        <!-- Botón para abrir/cerrar menú móvil -->
        <div class="md:hidden flex items-center">
          <button type="button" @click="isMenuOpen = !isMenuOpen" class="text-neutral-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lanzarote-blue" :aria-expanded="isMenuOpen" aria-controls="mobile-menu" :aria-label="t('nav.toggleMenu')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!isMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Menú de navegación para dispositivos móviles -->
      <div id="mobile-menu" v-show="isMenuOpen" class="md:hidden py-4 border-t border-neutral-volcanic">
        <div class="flex flex-col space-y-4">
          <button type="button" @click="scrollToPasos; isMenuOpen = false" class="text-neutral-dark hover:text-lanzarote-blue font-medium text-left">
            {{ t('nav.howItWorks') }}
          </button>
          <button type="button" @click="scrollToMunicipios; isMenuOpen = false" class="text-neutral-dark hover:text-lanzarote-blue font-medium text-left">
            {{ t('nav.municipalities') }}
          </button>
          <button type="button" @click="scrollToTestimonios; isMenuOpen = false" class="text-neutral-dark hover:text-lanzarote-blue font-medium text-left">
            {{ t('nav.reviews') }}
          </button>
          <button type="button" @click="scrollToFooter; isMenuOpen = false" class="text-neutral-dark hover:text-lanzarote-blue font-medium text-left">
            {{ t('nav.contact') }}
          </button>

          <div class="flex items-center gap-3">
            <span class="text-sm text-neutral-slate">{{ t('nav.language') }}</span>
            <select
              v-model="localeSeleccionado"
              @change="cambiarIdioma"
              class="border border-neutral-volcanic rounded-lg px-3 py-2 text-sm text-neutral-dark bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-lanzarote-blue"
              aria-label="Selector de idioma"
            >
              <option value="es">ES</option>
              <option value="en">EN</option>
            </select>
          </div>

          <button type="button" @click="showAuthModal = true; isMenuOpen = false" class="bg-lanzarote-blue text-white px-4 py-2 rounded-lg font-semibold hover:bg-lanzarote-yellow hover:text-black w-full">
            {{ t('nav.login') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de autenticación para login/registro -->
    <ModalAutenticacion v-model="showAuthModal" />
  </nav>
</template>


<script setup>
// Importación de utilidades y componentes
import { computed, ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'
import ModalAutenticacion from './Autenticacion/ModalAutenticacion.vue'

// Controla la visibilidad del modal de autenticación
const showAuthModal = ref(false)
// Controla el estado de apertura/cierre del menú móvil
const isMenuOpen = ref(false)

const { t, locale } = useI18n()
const page = usePage()

const localeActual = computed(() => page.props?.locale || 'es')
const localeSeleccionado = ref(localeActual.value)

// Mantener i18n sincronizado con el locale recibido del servidor
locale.value = localeActual.value

const cambiarIdioma = async () => {
  const anterior = locale.value
  const nuevo = localeSeleccionado.value

  // Aplicar inmediatamente en el frontend (mejor UX)
  locale.value = nuevo
  try {
    document.documentElement.lang = nuevo
  } catch {
  }

  try {
    await axios.post('/locale', { locale: nuevo })
  } catch (e) {
    // Si falla la persistencia, revertimos el UI
    locale.value = anterior
    localeSeleccionado.value = anterior
    try {
      document.documentElement.lang = anterior
    } catch {
    }
  }
}

// Scroll suave al pie de página
const scrollToFooter = () => {
  const footer = document.getElementById('contacto-footer')
  if (footer) {
    footer.scrollIntoView({ behavior: 'smooth' })
  }
}

// Scroll suave a la sección de municipios
const scrollToMunicipios = () => {
  const municipios = document.getElementById('municipios-section')
  if (municipios) {
    municipios.scrollIntoView({ behavior: 'smooth' })
  }
}

// Scroll suave a la sección de testimonios
const scrollToTestimonios = () => {
  const testimonios = document.getElementById('testimonios-section')
  if (testimonios) {
    testimonios.scrollIntoView({ behavior: 'smooth' })
  }
}

// Scroll suave a la sección "Cómo funciona"
const scrollToPasos = () => {
  const pasos = document.getElementById('como-funciona-section')
  if (pasos) {
    pasos.scrollIntoView({ behavior: 'smooth' })
  }
}

// Vuelve al inicio de la página
const goToHome = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>
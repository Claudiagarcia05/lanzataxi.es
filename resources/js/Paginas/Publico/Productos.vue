<template>
  <div class="min-h-screen bg-neutral-soft text-neutral-dark dark:bg-slate-950 dark:text-slate-100">
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="mb-8 flex items-center justify-between gap-4">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-lanzarote-blue dark:text-lanzarote-yellow">LanzaTaxi</p>
          <h1 class="mt-2 text-4xl md:text-5xl font-bold">{{ copy.title }}</h1>
        </div>
        <Link href="/" class="inline-flex items-center rounded-lg border border-neutral-volcanic/60 px-4 py-2 text-sm font-medium text-neutral-dark transition-colors hover:bg-white dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900">
          {{ copy.back }}
        </Link>
      </div>

      <section class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-neutral-volcanic/60 dark:bg-slate-900 dark:ring-slate-800">
        <p class="max-w-3xl text-lg leading-8 text-neutral-slate dark:text-slate-300">{{ copy.intro }}</p>

        <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
          <article v-for="product in copy.products" :key="product.title" class="rounded-3xl border border-neutral-volcanic/60 bg-neutral-soft p-6 transition-transform hover:-translate-y-1 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="inline-flex rounded-2xl bg-lanzarote-blue/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-lanzarote-blue dark:bg-lanzarote-blue/20 dark:text-white">
              {{ product.kicker }}
            </div>
            <h2 class="mt-4 text-2xl font-semibold">{{ product.title }}</h2>
            <p class="mt-3 leading-7 text-neutral-slate dark:text-slate-300">{{ product.body }}</p>
          </article>
        </div>
      </section>

      <section class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl bg-lanzarote-yellow p-8 text-neutral-dark shadow-lg">
          <h2 class="text-2xl font-semibold">{{ copy.sideTitle }}</h2>
          <p class="mt-3 max-w-2xl leading-7 text-neutral-dark/80">{{ copy.sideBody }}</p>
        </div>

        <div class="rounded-3xl bg-white p-8 ring-1 ring-neutral-volcanic/60 dark:bg-slate-900 dark:ring-slate-800">
          <h2 class="text-2xl font-semibold">{{ copy.ctaTitle }}</h2>
          <p class="mt-3 leading-7 text-neutral-slate dark:text-slate-300">{{ copy.ctaBody }}</p>
          <Link href="/soporte" class="mt-6 inline-flex rounded-lg bg-lanzarote-blue px-5 py-3 text-sm font-semibold text-white transition-colors hover:opacity-90">
            {{ copy.ctaButton }}
          </Link>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const copyByLocale = {
  es: {
    title: 'Productos',
    back: 'Volver al inicio',
    intro: 'LanzaTaxi se adapta a distintos tipos de usuario. En esta página agrupamos las opciones principales para que entiendas rápidamente qué puede hacer la plataforma para ti.',
    products: [
      {
        kicker: 'Movilidad',
        title: 'Pasajeros',
        body: 'Reserva taxi con seguimiento, cobertura por municipios y una experiencia directa pensada para moverte sin complicaciones.'
      },
      {
        kicker: 'Actividad',
        title: 'Taxistas',
        body: 'Herramientas para recibir servicios, consultar estado y centralizar la operativa diaria desde un único entorno.'
      },
      {
        kicker: 'Empresa',
        title: 'Empresas',
        body: 'Soluciones para desplazamientos corporativos, atención preferente y coordinación de viajes recurrentes.'
      }
    ],
    sideTitle: 'Qué gana cada perfil',
    sideBody: 'La idea no es ofrecer muchas páginas, sino una propuesta clara y enfocada en el uso real de cada perfil.',
    ctaTitle: '¿Quieres que te orientemos?',
    ctaBody: 'Si no sabes qué opción encaja contigo, te lo explicamos por soporte en pocos pasos.',
    ctaButton: 'Ir a soporte'
  },
  en: {
    title: 'Products',
    back: 'Back to home',
    intro: 'LanzaTaxi adapts to different user profiles. This page groups the main options so you can quickly understand what the platform can do for you.',
    products: [
      {
        kicker: 'Mobility',
        title: 'Passengers',
        body: 'Book a taxi with tracking, municipality coverage, and a straightforward experience designed to get you moving without friction.'
      },
      {
        kicker: 'Activity',
        title: 'Drivers',
        body: 'Tools to receive trips, check status, and manage day-to-day operations from a single place.'
      },
      {
        kicker: 'Business',
        title: 'Businesses',
        body: 'Solutions for corporate trips, priority support, and coordination of recurring rides.'
      },
      {
        kicker: 'Operations',
        title: 'Fleets',
        body: 'Built for teams and managers who need visibility and order in transport service operations.'
      }
    ],
    sideTitle: 'What each profile gets',
    sideBody: 'The goal is not to add many pages, but to keep one clear offering focused on real usage.',
    ctaTitle: 'Need guidance?',
    ctaBody: 'If you are not sure which option fits you best, support can explain it step by step.',
    ctaButton: 'Go to support'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
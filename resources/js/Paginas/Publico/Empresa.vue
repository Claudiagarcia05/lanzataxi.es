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
          <article v-for="card in copy.cards" :key="card.title" class="rounded-3xl border border-neutral-volcanic/60 bg-neutral-soft p-6 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="inline-flex rounded-2xl bg-lanzarote-blue/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-lanzarote-blue dark:bg-lanzarote-blue/20 dark:text-white">
              {{ card.kicker }}
            </div>
            <h2 class="mt-4 text-2xl font-semibold">{{ card.title }}</h2>
            <p class="mt-3 leading-7 text-neutral-slate dark:text-slate-300">{{ card.body }}</p>
          </article>
        </div>
      </section>

      <section class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <div class="rounded-3xl bg-lanzarote-yellow p-8 text-neutral-dark shadow-lg">
          <h2 class="text-2xl font-semibold">{{ copy.sideTitle }}</h2>
          <p class="mt-3 leading-7 text-neutral-dark/80">{{ copy.sideBody }}</p>
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
    title: 'Empresa',
    back: 'Volver al inicio',
    intro: 'LanzaTaxi nace para simplificar la movilidad en Lanzarote con una experiencia más clara, más rápida y mejor conectada con las necesidades de pasajeros, taxistas y empresas.',
    cards: [
      {
        kicker: 'Misión',
        title: 'Mover mejor la isla',
        body: 'Queremos que reservar un taxi sea sencillo y que el servicio llegue con más orden, trazabilidad y confianza.'
      },
      {
        kicker: 'Visión',
        title: 'Una plataforma útil',
        body: 'Buscamos que cada usuario encuentre su espacio: quien reserva, quien conduce y quien gestiona varios trayectos.'
      },
      {
        kicker: 'Cobertura',
        title: 'Enfoque local',
        body: 'La plataforma está pensada para operar con conocimiento del territorio y con foco en la realidad de la isla.'
      },
      {
        kicker: 'Servicio',
        title: 'Soporte cercano',
        body: 'No solo mostramos funciones; también acompañamos al usuario con ayuda real cuando la necesita.'
      }
    ],
    sideTitle: 'Cómo trabajamos',
    sideBody: 'Priorizamos claridad, utilidad y una experiencia que no obligue a aprender un sistema complejo para algo tan básico como moverse.',
    ctaTitle: '¿Quieres saber más?',
    ctaBody: 'Si te interesa cómo encaja LanzaTaxi en tu día a día, te lo contamos desde soporte o desde el resto de páginas públicas.',
    ctaButton: 'Ir a soporte'
  },
  en: {
    title: 'Company',
    back: 'Back to home',
    intro: 'LanzaTaxi was created to simplify mobility in Lanzarote with a clearer, faster experience that better matches the needs of passengers, drivers, and businesses.',
    cards: [
      {
        kicker: 'Mission',
        title: 'Move the island better',
        body: 'We want booking a taxi to be simple and the service to arrive with more order, traceability, and trust.'
      },
      {
        kicker: 'Vision',
        title: 'A useful platform',
        body: 'We aim for every user to find their place: the person booking, the driver, and the team managing repeated trips.'
      },
      {
        kicker: 'Coverage',
        title: 'Local focus',
        body: 'The platform is designed to operate with knowledge of the territory and with the island reality in mind.'
      },
      {
        kicker: 'Service',
        title: 'Close support',
        body: 'We do not just show features; we also back users with real help when they need it.'
      }
    ],
    sideTitle: 'How we work',
    sideBody: 'We prioritize clarity, usefulness, and an experience that does not force anyone to learn a complex system for something as basic as getting around.',
    ctaTitle: 'Want to know more?',
    ctaBody: 'If you want to understand how LanzaTaxi fits into your day-to-day, we can explain it through support or the public pages.',
    ctaButton: 'Go to support'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
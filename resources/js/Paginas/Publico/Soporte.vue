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

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
          <article v-for="item in copy.items" :key="item.title" class="rounded-3xl border border-neutral-volcanic/60 bg-neutral-soft p-6 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-lanzarote-blue dark:text-lanzarote-yellow">{{ item.kicker }}</div>
            <h2 class="mt-3 text-2xl font-semibold">{{ item.title }}</h2>
            <p class="mt-3 leading-7 text-neutral-slate dark:text-slate-300">{{ item.body }}</p>
          </article>
        </div>
      </section>

      <section class="mt-8 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl bg-lanzarote-blue p-8 text-white shadow-lg">
          <h2 class="text-2xl font-semibold">{{ copy.sideTitle }}</h2>
          <p class="mt-3 leading-7 text-white/85">{{ copy.sideBody }}</p>
        </div>

        <div class="rounded-3xl bg-white p-8 ring-1 ring-neutral-volcanic/60 dark:bg-slate-900 dark:ring-slate-800">
          <h2 class="text-2xl font-semibold">{{ copy.contactTitle }}</h2>
          <p class="mt-3 leading-7 text-neutral-slate dark:text-slate-300">{{ copy.contactBody }}</p>
          <a class="mt-6 inline-flex items-center rounded-lg bg-lanzarote-yellow px-5 py-3 text-sm font-semibold text-neutral-dark transition-colors hover:opacity-90" :href="`mailto:${contactEmail}`">
            {{ contactEmail }}
          </a>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const contactEmail = 'soporte@lanzataxi.es'
const { locale } = useI18n()

const copyByLocale = {
  es: {
    title: 'Soporte',
    back: 'Volver al inicio',
    intro: 'Si necesitas ayuda, este es el punto de entrada principal. Reunimos aquí las vías más útiles para resolver dudas sobre reservas, seguridad y uso general del servicio.',
    items: [
      {
        kicker: 'Reservas',
        title: 'Ayuda operativa',
        body: 'Si tienes un problema con una reserva o una incidencia puntual, escríbenos con el máximo detalle posible para responderte antes.'
      },
      {
        kicker: 'Confianza',
        title: 'Seguridad y acceso',
        body: 'Aquí también puedes consultar dudas sobre acceso, uso seguro del servicio y recomendaciones básicas para viajar con tranquilidad.'
      },
      {
        kicker: 'Documentación',
        title: 'Privacidad y legal',
        body: 'Si necesitas información sobre cookies, protección de datos o avisos legales, te redirigimos a los apartados publicados del sitio.'
      }
    ],
    sideTitle: 'Tiempo de respuesta',
    sideBody: 'Nuestra referencia es responder en menos de 24 horas laborables siempre que podamos.',
    contactTitle: 'Contacto directo',
    contactBody: 'Escríbenos a este correo y te orientamos según el tipo de consulta que tengas.'
  },
  en: {
    title: 'Support',
    back: 'Back to home',
    intro: 'If you need help, this is the main entry point. We gather here the most useful ways to solve questions about bookings, security, and general use of the service.',
    items: [
      {
        kicker: 'Bookings',
        title: 'Operational help',
        body: 'If you have a problem with a booking or a specific incident, write to us with as much detail as possible so we can respond faster.'
      },
      {
        kicker: 'Trust',
        title: 'Safety and access',
        body: 'You can also ask about account access, safe use of the service, and basic recommendations for a calm trip.'
      },
      {
        kicker: 'Documentation',
        title: 'Privacy and legal',
        body: 'If you need information about cookies, data protection, or legal notices, we point you to the published site sections.'
      }
    ],
    sideTitle: 'Response time',
    sideBody: 'Our target is to reply within 24 business hours whenever possible.',
    contactTitle: 'Direct contact',
    contactBody: 'Write to this email and we will guide you based on the type of question you have.'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
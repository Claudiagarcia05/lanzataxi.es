<template>
  <div class="min-h-screen bg-neutral-soft text-neutral-dark dark:bg-slate-950 dark:text-slate-100">
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="mb-8 flex items-center justify-between gap-4">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-lanzarote-blue dark:text-lanzarote-yellow">LanzaTaxi</p>
          <h1 class="mt-2 text-4xl md:text-5xl font-bold">{{ copy.title }}</h1>
        </div>
        <Link href="/" class="inline-flex items-center rounded-lg border border-neutral-volcanic/60 px-4 py-2 text-sm font-medium text-neutral-dark transition-colors hover:bg-white dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900">
          {{ copy.back }}
        </Link>
      </div>

      <div class="grid gap-8 lg:grid-cols-[1.4fr_0.8fr]">
        <section class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-neutral-volcanic/60 dark:bg-slate-900 dark:ring-slate-800">
          <p class="text-lg leading-8 text-neutral-slate dark:text-slate-300">{{ copy.intro }}</p>

          <div class="mt-8 space-y-6">
            <article v-for="item in copy.sections" :key="item.title" class="rounded-2xl bg-neutral-soft p-5 dark:bg-slate-950/60">
              <h2 class="text-xl font-semibold">{{ item.title }}</h2>
              <p class="mt-2 leading-7 text-neutral-slate dark:text-slate-300">{{ item.body }}</p>
            </article>
          </div>
        </section>

        <aside class="space-y-6">
          <section class="rounded-3xl bg-lanzarote-blue p-7 text-white shadow-lg">
            <h2 class="text-2xl font-semibold">{{ copy.sideTitle }}</h2>
            <p class="mt-3 leading-7 text-white/85">{{ copy.sideBody }}</p>
          </section>

          <section class="rounded-3xl bg-white p-7 ring-1 ring-neutral-volcanic/60 dark:bg-slate-900 dark:ring-slate-800">
            <h2 class="text-lg font-semibold">{{ copy.contactTitle }}</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-slate dark:text-slate-300">{{ copy.contactBody }}</p>
            <a class="mt-4 inline-flex text-lanzarote-blue hover:underline" :href="`mailto:${contactEmail}`">{{ contactEmail }}</a>
          </section>
        </aside>
      </div>
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
    title: 'Protección de datos',
    back: 'Volver al inicio',
    intro: 'Tratamos los datos personales necesarios para prestar el servicio, atender consultas y gestionar la relación con usuarios, taxistas y empresas. Cuando el tratamiento requiera una base jurídica concreta, se informará de forma clara y proporcional.',
    sections: [
      {
        title: 'Finalidades',
        body: 'Podemos usar tus datos para responder solicitudes, gestionar reservas, mejorar la experiencia de uso y cumplir con obligaciones legales o de seguridad.'
      },
      {
        title: 'Derechos',
        body: 'Puedes solicitar acceso, rectificación, supresión, oposición y limitación en los casos previstos por la normativa. Para ejercerlos, escribe al correo de soporte.'
      },
      {
        title: 'Conservación y seguridad',
        body: 'Conservamos la información solo durante el tiempo necesario para la finalidad descrita o el plazo exigido por ley, aplicando medidas razonables de seguridad.'
      }
    ],
    sideTitle: 'Compromiso',
    sideBody: 'Queremos que el tratamiento de datos sea claro, mínimo y alineado con el servicio real que se presta.',
    contactTitle: 'Ejercicio de derechos',
    contactBody: 'Si deseas revisar, corregir o eliminar tus datos, contacta con soporte y te ayudaremos con el proceso.'
  },
  en: {
    title: 'Data protection',
    back: 'Back to home',
    intro: 'We process the personal data needed to provide the service, answer inquiries, and manage the relationship with users, drivers, and businesses. When a specific legal basis is required, we will explain it clearly and proportionately.',
    sections: [
      {
        title: 'Purposes',
        body: 'We may use your data to answer requests, manage bookings, improve the user experience, and meet legal or security obligations.'
      },
      {
        title: 'Your rights',
        body: 'You may request access, correction, deletion, objection, and restriction where applicable under the law. To exercise them, write to the support email.'
      },
      {
        title: 'Retention and security',
        body: 'We retain information only for as long as needed for the stated purpose or for the period required by law, applying reasonable security measures.'
      }
    ],
    sideTitle: 'Commitment',
    sideBody: 'We want data processing to be clear, minimal, and aligned with the actual service provided.',
    contactTitle: 'Exercising your rights',
    contactBody: 'If you want to review, correct, or delete your data, contact support and we will help you with the process.'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
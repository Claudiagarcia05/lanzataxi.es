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
    title: 'Aviso legal',
    back: 'Volver al inicio',
    intro: 'Este sitio opera bajo la marca LanzaTaxi y ofrece información, soporte y acceso a los servicios publicados. Si necesitas los datos completos del titular o una referencia concreta, contacta con soporte y te los facilitamos.',
    sections: [
      {
        title: 'Uso correcto del sitio',
        body: 'La información publicada tiene finalidad informativa y de asistencia. No está permitido usar el sitio para fines ilícitos, automatizar acciones no autorizadas o interferir con el servicio.'
      },
      {
        title: 'Responsabilidad',
        body: 'Hacemos esfuerzos razonables para mantener la información actualizada, pero puede haber cambios en disponibilidad, horarios o condiciones operativas. Verifica siempre por soporte cualquier dato sensible.'
      },
      {
        title: 'Contacto legal',
        body: 'Para solicitudes de propiedad intelectual, cumplimiento normativo o cualquier consulta legal, utiliza el correo de soporte y te responderemos con la documentación correspondiente.'
      }
    ],
    sideTitle: 'Resumen rápido',
    sideBody: 'Este aviso resume el uso permitido de la web y cómo gestionamos las consultas formales.',
    contactTitle: 'Soporte legal',
    contactBody: 'Si necesitas un documento completo, el equipo puede ayudarte desde el correo de contacto.'
  },
  en: {
    title: 'Legal notice',
    back: 'Back to home',
    intro: 'This website operates under the LanzaTaxi brand and provides information, support, and access to the services published on the site. If you need the full owner details or a specific reference, contact support and we will send it over.',
    sections: [
      {
        title: 'Proper use of the site',
        body: 'The published information is intended for information and support purposes. You may not use the site for unlawful purposes, automate unauthorized actions, or interfere with the service.'
      },
      {
        title: 'Liability',
        body: 'We make reasonable efforts to keep the information up to date, but availability, schedules, or operating conditions may change. Always confirm any sensitive data with support.'
      },
      {
        title: 'Legal contact',
        body: 'For intellectual property requests, compliance matters, or any legal question, use the support email and we will reply with the relevant documentation.'
      }
    ],
    sideTitle: 'Quick summary',
    sideBody: 'This notice summarizes allowed site usage and how we handle formal requests.',
    contactTitle: 'Legal support',
    contactBody: 'If you need a full document, the team can help you via the contact email.'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
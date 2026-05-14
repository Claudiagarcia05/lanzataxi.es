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
          <section class="rounded-3xl bg-lanzarote-yellow p-7 text-neutral-dark shadow-lg">
            <h2 class="text-2xl font-semibold">{{ copy.sideTitle }}</h2>
            <p class="mt-3 leading-7 text-neutral-dark/80">{{ copy.sideBody }}</p>
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
    title: 'Política de cookies',
    back: 'Volver al inicio',
    intro: 'Utilizamos cookies y tecnologías similares para mantener la sesión, recordar el idioma y reforzar la seguridad del sitio. Si en algún momento añadimos analítica o preferencias adicionales, se informará de forma clara.',
    sections: [
      {
        title: 'Cookies necesarias',
        body: 'Son las que permiten que la web funcione correctamente: navegación básica, preferencias de idioma, formularios y seguridad de sesión.'
      },
      {
        title: 'Cookies de preferencias',
        body: 'Guardan ajustes útiles para que la experiencia sea más cómoda, como el idioma seleccionado o pequeñas personalizaciones de la interfaz.'
      },
      {
        title: 'Gestión de cookies',
        body: 'Puedes borrar o bloquear cookies desde tu navegador. Si haces cambios, algunas funciones pueden dejar de estar disponibles o recordar menos información.'
      }
    ],
    sideTitle: 'Qué no hacemos',
    sideBody: 'No usamos cookies para invadir tu privacidad ni para vender tus datos. El objetivo es que la plataforma funcione bien.',
    contactTitle: 'Dudas sobre cookies',
    contactBody: 'Si quieres una aclaración concreta sobre alguna cookie o permiso, escríbenos y te respondemos.'
  },
  en: {
    title: 'Cookies policy',
    back: 'Back to home',
    intro: 'We use cookies and similar technologies to keep the session active, remember the language, and reinforce site security. If we ever add analytics or extra preferences, we will clearly inform users.',
    sections: [
      {
        title: 'Necessary cookies',
        body: 'These allow the site to work properly: basic navigation, language preferences, forms, and session security.'
      },
      {
        title: 'Preference cookies',
        body: 'They store useful settings so the experience is more comfortable, such as the selected language or small interface customizations.'
      },
      {
        title: 'Cookie management',
        body: 'You can delete or block cookies from your browser. If you do, some features may stop working or remember less information.'
      }
    ],
    sideTitle: 'What we do not do',
    sideBody: 'We do not use cookies to intrude on your privacy or to sell your data. The goal is to keep the platform working well.',
    contactTitle: 'Cookie questions',
    contactBody: 'If you want a specific clarification about a cookie or permission, write to us and we will reply.'
  }
}

const copy = computed(() => (String(locale.value || 'es').startsWith('en') ? copyByLocale.en : copyByLocale.es))
</script>
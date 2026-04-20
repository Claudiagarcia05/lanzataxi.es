import { createI18n } from 'vue-i18n'

import es from './locales/es.json'
import en from './locales/en.json'

/**
 * Configuración central de i18n (vue-i18n).
 *
 * Este módulo:
 * - Declara los idiomas soportados por la app.
 * - Resuelve el locale inicial a partir de props del backend (Inertia) o del
 *   atributo `lang` del documento HTML.
 * - Crea la instancia de `createI18n` en modo Composition API (`legacy: false`).
 */

// Lista de idiomas soportados. Mantener en sync con los JSON de `./locales/`.
export const LOCALES_DISPONIBLES = ['es', 'en']

/**
 * Determina el idioma inicial de la interfaz.
 *
 * Prioridad:
 * 1) `pageProps.locale` (lo suele enviar el backend en las props de Inertia).
 * 2) `<html lang="...">` (útil en render del servidor o fallback de navegadores).
 * 3) Español por defecto.
 */
export function resolverLocaleInicial(pageProps = {}) {
  const deProps = pageProps.locale
  if (typeof deProps === 'string' && LOCALES_DISPONIBLES.includes(deProps)) return deProps

  // Evita romper en SSR/entornos sin DOM: comprobamos `document`.
  const htmlLang = typeof document !== 'undefined' ? document.documentElement.lang : null
  if (htmlLang) {
    // Admite formatos tipo `es-ES` / `en-GB` quedándonos con la parte base.
    const base = htmlLang.split('-')[0]
    if (LOCALES_DISPONIBLES.includes(base)) return base
  }

  return 'es'
}

/**
 * Crea una instancia de `vue-i18n`.
 *
 * - `fallbackLocale` se fija a 'es' para que, si falta una clave en otro idioma,
 *   siempre haya un texto de respaldo.
 */
export function crearI18n(localeInicial = 'es') {

  return createI18n({
    legacy: false,
    locale: localeInicial,
    fallbackLocale: 'es',
    messages: {
      es,
      en,
    },
  })
}
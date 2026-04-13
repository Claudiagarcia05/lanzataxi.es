import { createI18n } from 'vue-i18n'

import es from './locales/es.json'
import en from './locales/en.json'

export const LOCALES_DISPONIBLES = ['es', 'en']

export function resolverLocaleInicial(pageProps = {}) {
  const deProps = pageProps.locale
  if (typeof deProps === 'string' && LOCALES_DISPONIBLES.includes(deProps)) return deProps

  const htmlLang = typeof document !== 'undefined' ? document.documentElement.lang : null
  if (htmlLang) {
    const base = htmlLang.split('-')[0]
    if (LOCALES_DISPONIBLES.includes(base)) return base
  }

  return 'es'
}

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

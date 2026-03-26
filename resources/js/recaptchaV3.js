let scriptLoadingPromise = null

function getSiteKey() {
  return import.meta.env.VITE_RECAPTCHA_SITE_KEY || ''
}

function loadRecaptchaScript(siteKey) {
  if (typeof window === 'undefined') return Promise.reject(new Error('No window'))
  if (window.grecaptcha) return Promise.resolve()
  if (scriptLoadingPromise) return scriptLoadingPromise

  scriptLoadingPromise = new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-recaptcha-v3="true"]')
    if (existing) {
      existing.addEventListener('load', () => resolve())
      existing.addEventListener('error', () => reject(new Error('reCAPTCHA load error')))

      return
    }

    const script = document.createElement('script')
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`
    script.async = true
    script.defer = true
    script.setAttribute('data-recaptcha-v3', 'true')
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('reCAPTCHA load error'))

    document.head.appendChild(script)
  })

  return scriptLoadingPromise
}

export async function executeRecaptchaV3(action) {
  const siteKey = getSiteKey()
  if (!siteKey) return null

  await loadRecaptchaScript(siteKey)

  if (!window.grecaptcha) throw new Error('grecaptcha not available')

  return await new Promise((resolve, reject) => {
    window.grecaptcha.ready(() => {
      window.grecaptcha
        .execute(siteKey, { action })
        .then(resolve)
        .catch(reject)
    })
  })
}

// Estado compartido del módulo para evitar inyectar el script de reCAPTCHA varias veces.
// Si distintos componentes llaman a `executeRecaptchaV3()` casi a la vez, todos esperan
// a la misma promesa de carga.
let scriptLoadingPromise = null

function getSiteKey() {
  // La clave pública (site key) se inyecta vía variables de entorno de Vite.
  // Si no existe, el cliente debe tratar reCAPTCHA como "no configurado".
  return import.meta.env.VITE_RECAPTCHA_SITE_KEY || ''
}

function loadRecaptchaScript(siteKey) {
  // Este helper SOLO es válido en navegador; si alguien lo ejecuta en SSR/Node
  // (por ejemplo, tests), fallamos explícitamente.
  if (typeof window === 'undefined') return Promise.reject(new Error('No window'))

  // Si Google ya expuso `grecaptcha`, la librería está lista.
  if (window.grecaptcha) return Promise.resolve()

  // Si ya hay una carga en curso, reutilizamos la misma promesa.
  if (scriptLoadingPromise) return scriptLoadingPromise

  scriptLoadingPromise = new Promise((resolve, reject) => {
    // Si el script ya está en el DOM (navegación previa, HMR, etc.), escuchamos sus eventos.
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

  // Caso "degradado": si no hay clave configurada, devolvemos `null`.
  // El backend debe permitir esta ausencia (o bloquearlo) según la política de seguridad.
  if (!siteKey) return null

  await loadRecaptchaScript(siteKey)

  if (!window.grecaptcha) throw new Error('grecaptcha not available')

  // `grecaptcha.ready` garantiza que la librería terminó de inicializar antes de ejecutar.
  // Retornamos el token para enviarlo al backend (verificación server-side obligatoria).
  return await new Promise((resolve, reject) => {
    window.grecaptcha.ready(() => {
      window.grecaptcha
        .execute(siteKey, { action })
        .then(resolve)
        .catch(reject)
    })
  })
}
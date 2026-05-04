const buildUrl = (url, params) => {
  if (!params || Object.keys(params).length === 0) return url

  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    query.append(key, String(value))
  })

  const qs = query.toString()
  if (!qs) return url

  return `${url}${url.includes('?') ? '&' : '?'}${qs}`
}

const getCookie = (name) => {
  const prefix = `${name}=`
  const found = document.cookie
    .split(';')
    .map((part) => part.trim())
    .find((part) => part.startsWith(prefix))

  return found ? decodeURIComponent(found.slice(prefix.length)) : null
}

export const apiFetch = async (url, options = {}) => {
  const {
    method = 'GET',
    params,
    body,
    headers = {},
    cache = 'no-store',
    signal,
  } = options

  const token = localStorage.getItem('token')
  const csrfFromMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  const xsrfToken = getCookie('XSRF-TOKEN')

  const normalizedMethod = method.toUpperCase()
  const finalHeaders = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...headers,
  }

  if (token) {
    finalHeaders.Authorization = `Bearer ${token}`
  }

  if (normalizedMethod !== 'GET') {
    finalHeaders['Content-Type'] = finalHeaders['Content-Type'] || 'application/json'
    if (csrfFromMeta) finalHeaders['X-CSRF-TOKEN'] = csrfFromMeta
    if (xsrfToken) finalHeaders['X-XSRF-TOKEN'] = xsrfToken
  }

  const response = await fetch(buildUrl(url, params), {
    method: normalizedMethod,
    credentials: 'same-origin',
    headers: finalHeaders,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    cache,
    signal,
  })

  const contentType = response.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const payload = isJson ? await response.json() : await response.text()

  if (!response.ok) {
    const message = typeof payload === 'object' && payload?.message
      ? payload.message
      : `HTTP ${response.status}`

    const error = new Error(message)
    error.status = response.status
    error.payload = payload
    throw error
  }

  return payload
}

import { defineStore } from 'pinia'
import axios from 'axios'

/**
 * Normaliza rutas/URLs de avatar.
 *
 * En la app pueden venir distintos formatos desde backend o desde storage
 * (absoluto, relativo, /storage, etc.). Esta función intenta unificar para
 * que el frontend renderice de forma consistente.
 */
const normalizarUrlAvatar = (avatar) => {
    if (typeof avatar !== 'string') return avatar

    const avatarRecortado = avatar.trim()
    if (!avatarRecortado) return null

    if (/^https?:\/\//i.test(avatarRecortado)) return avatarRecortado
    if (avatarRecortado.startsWith('/storage/')) return avatarRecortado
    if (avatarRecortado.startsWith('storage/')) return `/${avatarRecortado}`

    if (avatarRecortado.startsWith('/avatars/')) return `/storage${avatarRecortado}`

    if (!avatarRecortado.startsWith('/')) return `/storage/${avatarRecortado}`

    return avatarRecortado
}

/**
 * Normaliza el objeto usuario para garantizar propiedades usadas por el frontend.
 */
const normalizarUsuario = (usuario) => {
    if (!usuario || typeof usuario !== 'object') return usuario

    if (!('avatar' in usuario)) return usuario

    return {
        ...usuario,
        avatar: normalizarUrlAvatar(usuario.avatar),
    }
}

/**
 * Store de autenticación.
 *
 * Responsabilidades:
 * - login/registro vía API (`/api/login`, `/api/register`)
 * - persistencia del token (localStorage + cookie)
 * - sincronización del usuario (`/api/me`)
 *
 * Nota: se configura el header global de axios `Authorization: Bearer <token>`.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        usuario: null,
        token: localStorage.getItem('token') || null,
        cargando: false,
        error: null,
        inicializado: false 
    }),

    getters: {
        estaAutenticado: (state) => !!state.token,
        ispasajero: (state) => state.usuario?.role === 'pasajero',
        isconductor: (state) => state.usuario?.role === 'conductor',
        isAdmin: (state) => state.usuario?.role === 'admin',
        nombreUsuario: (state) => state.usuario?.name || 'Usuario',
        correoUsuario: (state) => state.usuario?.email || ''
    },

    actions: {
        async iniciarSesion(credenciales) {
            // Inicia sesión contra la API. Si va bien, guarda token/usuario.
            this.cargando = true
            this.error = null

            try {
                const respuesta = await axios.post('/api/login', credenciales)
                const { token, user: usuario } = respuesta.data
                const usuarioNormalizado = normalizarUsuario(usuario)

                this.usuario = usuarioNormalizado
                this.token = token
                localStorage.setItem('token', token)
                localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))

                const fechaExpiracion = new Date()
                fechaExpiracion.setDate(fechaExpiracion.getDate() + 7)
                document.cookie = `token=${token}; path=/; SameSite=Lax; expires=${fechaExpiracion.toUTCString()}`

                // Header por defecto para el resto de requests.
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

                return { success: true }
            } catch (errorCapturado) {
                const estado = errorCapturado.response?.status
                const mensaje = errorCapturado.response?.data?.message || 'No se pudo iniciar sesión'

                if (estado === 401) {
                    // Si el token/credenciales no son válidos, limpiamos estado local.
                    this.usuario = null
                    this.token = null
                    localStorage.removeItem('token')
                    localStorage.removeItem('usuario')
                    delete axios.defaults.headers.common['Authorization']
                }

                this.error = mensaje

                return { success: false, error: mensaje }
            } finally {
                this.cargando = false
            }
        },

        async registrar(datosUsuario) {
            // Registro vía API. Devuelve token y usuario.
            this.cargando = true
            this.error = null

            try {
                const respuesta = await axios.post('/api/register', datosUsuario)
                const { token, user: usuario } = respuesta.data
                const usuarioNormalizado = normalizarUsuario(usuario)

                this.usuario = usuarioNormalizado
                this.token = token
                localStorage.setItem('token', token)
                localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))

                const fechaExpiracion = new Date()
                fechaExpiracion.setDate(fechaExpiracion.getDate() + 7)
                document.cookie = `token=${token}; path=/; SameSite=Lax; expires=${fechaExpiracion.toUTCString()}`

                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

                return { success: true, usuario: usuarioNormalizado }
            } catch (errorCapturado) {
                const mensaje = errorCapturado.response?.data?.message || 'No se pudo registrar el usuario'
                const errores = errorCapturado.response?.data?.errors || {}
                this.error = mensaje

                return { success: false, error: mensaje, errors: errores }
            } finally {
                this.cargando = false
            }
        },

        async cerrarSesion() {
            // Intenta cerrar sesión tanto en web (`/logout`) como en API (`/api/logout`).
            // Se ignoran errores para asegurar limpieza del estado local.
            try {
                await axios.post('/logout')
            } catch {
            }

            try {
                if (this.token) {
                    await axios.post('/api/logout')
                }
            } catch {
            }

            this.usuario = null
            this.token = null
            localStorage.removeItem('token')
            localStorage.removeItem('usuario')
            delete axios.defaults.headers.common['Authorization']

            document.cookie = 'token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax'
        },

        async verificarAutenticacion() {
            // Sincroniza token/usuario desde localStorage o cookie.
            // Si no hay nada local, intenta descubrir sesión válida vía `/api/me`.
            const obtenerCookie = (nombreCookie) => {
                const valorCookie = `; ${document.cookie}`
                const partesCookie = valorCookie.split(`; ${nombreCookie}=`)
                if (partesCookie.length === 2) return partesCookie.pop().split(';').shift()

                return null
            }

            let token = localStorage.getItem('token')
            let cadenaUsuario = localStorage.getItem('usuario')

            if (!token && !cadenaUsuario) {
                try {
                    // Caso: sesión server-side válida aunque no tengamos token en local.
                    const response = await axios.get('/api/me')
                    const usuarioNormalizado = normalizarUsuario(response.data)
                    this.usuario = usuarioNormalizado
                    localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                    this.inicializado = true

                    return true
                } catch {
                }
            }

            if (!token) {
                token = obtenerCookie('token')
                if (token) {
                    console.log('Token recuperado desde cookie')
                    localStorage.setItem('token', token)
                }
            }

            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                this.token = token

                if (!cadenaUsuario) {
                    console.log('Sincronizando usuario desde el servidor...')
                    try {
                        const response = await axios.get('/api/me')
                        const usuarioNormalizado = normalizarUsuario(response.data)
                        this.usuario = usuarioNormalizado
                        localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                        this.inicializado = true

                        return true
                    } catch (error) {
                        console.error('Error al sincronizar usuario:', error)
                        this.token = null
                        this.usuario = null
                        localStorage.removeItem('token')
                        delete axios.defaults.headers.common['Authorization']
                        this.inicializado = true

                        return false
                    }
                } else {
                    try {
                        // Si tenemos usuario en localStorage, lo reutilizamos para arrancar rápido.
                        const usuario = JSON.parse(cadenaUsuario)
                        const usuarioNormalizado = normalizarUsuario(usuario)
                        this.usuario = usuarioNormalizado
                        localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                        this.inicializado = true

                        return true
                    } catch (error) {
                        console.error('Error al parsear usuario desde localStorage:', error)
                        this.usuario = null
                        this.token = null
                        localStorage.removeItem('token')
                        localStorage.removeItem('usuario')
                        delete axios.defaults.headers.common['Authorization']
                        this.inicializado = true

                        return false
                    }
                }
            }
            
            this.inicializado = true

            return false
        },

        obtenerRutaDashboard() {
            // Determina la ruta post-login según rol.
            if (!this.usuario) return '/'
            
            switch (this.usuario.role) {
                case 'conductor':

                    return '/conductor/dashboard'
                case 'admin':

                    return '/administradir/home'
                case 'pasajero':
                default:

                    return '/dashboard'
            }
        },

        async sincronizarUsuario() {
            // Vuelve a pedir el usuario al servidor para refrescar datos (avatar/phone/rol...).
            if (!this.token) return false

            try {
                const response = await axios.get('/api/me')
                const usuarioNormalizado = normalizarUsuario(response.data)
                this.usuario = usuarioNormalizado
                localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                console.log('Usuario sincronizado desde el servidor:', this.usuario)

                return true
            } catch (error) {
                console.error('Error al sincronizar usuario:', error)
                
                return false
            }
        }
    }
})
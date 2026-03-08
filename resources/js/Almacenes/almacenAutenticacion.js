import { defineStore } from 'pinia'
import axios from 'axios'

const normalizeAvatarUrl = (avatar) => {
    if (typeof avatar !== 'string') return avatar

    const trimmed = avatar.trim()
    if (!trimmed) return null

    if (/^https?:\/\//i.test(trimmed)) return trimmed
    if (trimmed.startsWith('/storage/')) return trimmed
    if (trimmed.startsWith('storage/')) return `/${trimmed}`

    // Si por algún motivo viene como "/avatars/...", en esta app vive bajo "/storage/avatars/...".
    if (trimmed.startsWith('/avatars/')) return `/storage${trimmed}`

    // Caso normal: DB guarda "avatars/...".
    if (!trimmed.startsWith('/')) return `/storage/${trimmed}`

    return trimmed
}

const normalizeUser = (user) => {
    if (!user || typeof user !== 'object') return user

    if (!('avatar' in user)) return user

    return {
        ...user,
        avatar: normalizeAvatarUrl(user.avatar),
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        usuario: null,
        token: localStorage.getItem('token') || null,
        cargando: false,
        error: null,
        initialized: false  // Indica si checkAuth() ha sido ejecutado
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        ispasajero: (state) => state.usuario?.role === 'pasajero',
        isconductor: (state) => state.usuario?.role === 'conductor',
        isAdmin: (state) => state.usuario?.role === 'admin',
        nombreUsuario: (state) => state.usuario?.name || 'Usuario',
        correoUsuario: (state) => state.usuario?.email || ''
    },

    actions: {
        async login(credentials) {
            this.cargando = true
            this.error = null

            try {
                const response = await axios.post('/api/login', credentials)
                const { token, user: usuario } = response.data
                const usuarioNormalizado = normalizeUser(usuario)

                this.usuario = usuarioNormalizado
                this.token = token
                localStorage.setItem('token', token)
                localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))

                // ⭐ Guardar token en cookie para que Laravel lo reconozca (7 días)
                const expirationDate = new Date()
                expirationDate.setDate(expirationDate.getDate() + 7)
                document.cookie = `token=${token}; path=/; SameSite=Lax; expires=${expirationDate.toUTCString()}`

                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

                return { success: true }
            } catch (error) {
                const status = error.response?.status
                const message = error.response?.data?.message || 'No se pudo iniciar sesión'

                // Si el backend responde 401 (credenciales inválidas), asegurar estado "deslogueado"
                // para evitar que se reutilice un token/usuario anterior.
                if (status === 401) {
                    this.usuario = null
                    this.token = null
                    localStorage.removeItem('token')
                    localStorage.removeItem('usuario')
                    delete axios.defaults.headers.common['Authorization']
                }

                this.error = message
                return { success: false, error: message }
            } finally {
                this.cargando = false
            }
        },

        async register(datosUsuario) {
            this.cargando = true
            this.error = null

            try {
                const response = await axios.post('/api/register', datosUsuario)
                const { token, user: usuario } = response.data
                const usuarioNormalizado = normalizeUser(usuario)

                // Autenticar automáticamente después del registro
                this.usuario = usuarioNormalizado
                this.token = token
                localStorage.setItem('token', token)
                localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))

                // ⭐ Guardar token en cookie para que Laravel lo reconozca (7 días)
                const expirationDate = new Date()
                expirationDate.setDate(expirationDate.getDate() + 7)
                document.cookie = `token=${token}; path=/; SameSite=Lax; expires=${expirationDate.toUTCString()}`

                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

                return { success: true, usuario: usuarioNormalizado }
            } catch (error) {
                const message = error.response?.data?.message || 'No se pudo registrar el usuario'
                const errors = error.response?.data?.errors || {}
                this.error = message
                return { success: false, error: message, errors }
            } finally {
                this.cargando = false
            }
        },

        async logout() {
            try {
                // 1) Cerrar sesión web (Inertia/Laravel) para que las rutas con middleware `auth`
                //    no sigan permitiendo acceso.
                await axios.post('/logout')
            } catch {
            }

            try {
                // 2) Revocar token API si existe
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

            // 3) Borrar cookie `token` (se usaba para rehidratar auth). Si no se borra,
            //    `checkAuth()` puede volver a autenticar desde cookie después del logout.
            document.cookie = 'token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax'
        },

        async checkAuth() {
            // Helper para leer cookies
            const getCookie = (name) => {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            let token = localStorage.getItem('token')
            let cadenaUsuario = localStorage.getItem('usuario')

            // Si no hay token ni usuario guardado, puede que estemos autenticados por sesión (web).
            // Intentar sincronizar usuario desde el servidor (auth:sanctum con cookies).
            if (!token && !cadenaUsuario) {
                try {
                    const response = await axios.get('/api/me')
                    const usuarioNormalizado = normalizeUser(response.data)
                    this.usuario = usuarioNormalizado
                    localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                    this.initialized = true
                    return true
                } catch {
                    // No hay sesión válida o no es stateful; continuar con el flujo normal
                }
            }

            // Si no hay token en localStorage, intentar desde cookie
            if (!token) {
                token = getCookie('token')
                if (token) {
                    console.log('Token recuperado desde cookie')
                    localStorage.setItem('token', token)
                }
            }

            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                this.token = token

                // Si tenemos token pero no usuario, sincronizar desde servidor
                if (!cadenaUsuario) {
                    console.log('Sincronizando usuario desde el servidor...')
                    try {
                        const response = await axios.get('/api/me')
                        const usuarioNormalizado = normalizeUser(response.data)
                        this.usuario = usuarioNormalizado
                        localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                        this.initialized = true
                        return true
                    } catch (error) {
                        console.error('Error al sincronizar usuario:', error)
                        this.token = null
                        this.usuario = null
                        localStorage.removeItem('token')
                        delete axios.defaults.headers.common['Authorization']
                        this.initialized = true
                        return false
                    }
                } else {
                    try {
                        // Parsear el usuario guardado
                        const usuario = JSON.parse(cadenaUsuario)
                        const usuarioNormalizado = normalizeUser(usuario)
                        this.usuario = usuarioNormalizado
                        localStorage.setItem('usuario', JSON.stringify(usuarioNormalizado))
                        this.initialized = true
                        return true
                    } catch (error) {
                        console.error('Error parsing usuario from localStorage:', error)
                        this.usuario = null
                        this.token = null
                        localStorage.removeItem('token')
                        localStorage.removeItem('usuario')
                        delete axios.defaults.headers.common['Authorization']
                        this.initialized = true
                        return false
                    }
                }
            }
            
            this.initialized = true
            return false
        },

        // Función para obtener la ruta del dashboard según el rol del usuario
        getDashboardRoute() {
            if (!this.usuario) return '/'
            
            switch (this.usuario.role) {
                case 'conductor':
                    return '/conductor/dashboard'
                case 'admin':
                    return '/admin/dashboard'
                case 'pasajero':
                default:
                    return '/dashboard'
            }
        },

        // Sincronizar usuario desde el servidor
        async syncUser() {
            if (!this.token) return false

            try {
                const response = await axios.get('/api/me')
                const usuarioNormalizado = normalizeUser(response.data)
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


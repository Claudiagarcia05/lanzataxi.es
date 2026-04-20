import { defineStore } from 'pinia'
import { useAuthStore } from './almacenAutenticacion'
import axios from 'axios'

/**
 * Store de viajes.
 *
 * Responsabilidades:
 * - listar viajes para pasajero/conductor/admin (endpoint cambia según contexto)
 * - crear/aceptar/iniciar/completar/cancelar/valorar viajes
 * - sondeo (polling) para refrescar la lista
 *
 * Nota: este store traduce algunos estados del backend a strings usados en UI.
 */
export const useViajeStore = defineStore('viaje', {
    state: () => ({
        viajes: [],
        viajeActivo: null,
        cargando: false,
        error: null,
        ubicacionConductor: null,
        idSondeo: null,
        idsOfertasIgnoradas: []
    }),

    getters: {
        viajesPasajero: (state) => {

            return state.viajes
        },
        viajesConductor: (state) => {
            const autenticacion = useAuthStore()
            const idUsuario = autenticacion.usuario?.id
            if (idUsuario == null) {

                return state.viajes.filter(t => t.conductorEntityId != null)
            }

            return state.viajes.filter(t => Number(t.conductorId) === Number(idUsuario))
        },
        viajesPendientes: (state) => state.viajes.filter(t => t.estado === 'pendiente'),
        viajesCompletados: (state) => state.viajes.filter(t => t.estado === 'completed'),
        viajesHoy: (state) => {
            const hoy = new Date().toDateString()

            return state.viajes.filter(t => new Date(t.date).toDateString() === hoy)
        }
    },

    actions: {
        /**
         * Convierte un valor a número finito o null.
         * Útil cuando el backend devuelve strings/valores vacíos.
         */
        parsearNumeroFinito(valor) {
            const numero = typeof valor === 'number' ? valor : parseFloat(valor)
            
            return Number.isFinite(numero) ? numero : null
        },

        mapearViaje(viaje) {
            const autenticacion = useAuthStore()

            const rutaActual = typeof window !== 'undefined' ? window.location.pathname : ''
            const esContextoConductor = autenticacion.isconductor || rutaActual.startsWith('/conductor')

            // Mapa de estados del backend -> estados mostrados en UI.
            const statusMap = {
                pending: 'pendiente',
                accepted: 'accepted',
                in_progress: 'in_progress',
                completed: 'completed',
                cancelled: 'cancelled',
            }

            return {
                id: viaje.id,
                pasajeroId: viaje.pasajero_id,
                pasajeroName: viaje.pasajero?.name || 'Pasajero',
                conductorId: viaje.conductor?.user?.id || null,
                conductorEntityId: viaje.conductor_id,
                conductorName: viaje.conductor?.user?.name || null,
                conductor: viaje.conductor || null,
                taxiId: viaje.taxi_id,
                pickup: viaje.pickup_address || `(${viaje.pickup_lat}, ${viaje.pickup_lng})`,
                dropoff: viaje.dropoff_address || `(${viaje.dropoff_lat}, ${viaje.dropoff_lng})`,
                pickup_address: viaje.pickup_address,
                dropoff_address: viaje.dropoff_address,
                pickupLat: this.parsearNumeroFinito(viaje.pickup_lat),
                pickupLng: this.parsearNumeroFinito(viaje.pickup_lng),
                dropoffLat: this.parsearNumeroFinito(viaje.dropoff_lat),
                dropoffLng: this.parsearNumeroFinito(viaje.dropoff_lng),
                date: viaje.created_at,
                created_at: viaje.created_at,
                endTime: viaje.end_time,
                end_time: viaje.end_time,
                scheduled_for: viaje.scheduled_for,
                // `estado` es el estado normalizado para UI.
                estado: statusMap[viaje.status] || viaje.status,
                price: viaje.price ? Number(viaje.price) : 0,
                distance: viaje.distance ? Number(viaje.distance) : 0,
                co2Saved: viaje.co2_saved ? Number(viaje.co2_saved) : 0,
                valoracion: viaje.rating,
                comment: viaje.comment,
                pasajeros: viaje.pasajeros || 1,
                luggage: viaje.luggage || 0,
                pago_method: viaje.pago_method || 'cash',
                notes: viaje.notes,
                pago: viaje.pago || null,
            }
        },

        iniciarSondeo(intervaloMs = 5000) {
            // Polling para refrescar viajes (se detiene/reinicia automáticamente).
            this.detenerSondeo()
            this.idSondeo = setInterval(() => {
                this.obtenerViajes()
            }, intervaloMs)
        },

        detenerSondeo() {
            if (this.idSondeo) {
                clearInterval(this.idSondeo)
                this.idSondeo = null
            }
        },

        async obtenerViajes() {
            // Obtiene viajes según rol/contexto. En conductor, además añade ofertas disponibles.
            this.cargando = true
            this.error = null
            try {
                const autenticacion = useAuthStore()
                let endpoint = '/api/user/viajes'

                const rutaActual = typeof window !== 'undefined' ? window.location.pathname : ''
                const asumirConductor = autenticacion.isconductor || rutaActual.startsWith('/conductor')
                const asumirAdmin = autenticacion.isAdmin || rutaActual.startsWith('/admin')

                if (asumirConductor) {
                    endpoint = '/api/conductor/viajes'
                } else if (asumirAdmin) {
                    endpoint = '/api/admin/viajes'
                }

                if (asumirConductor) {
                    const respuestaMisViajes = await axios.get(endpoint)
                    const misViajes = (respuestaMisViajes.data || []).map(this.mapearViaje)

                    // Si el conductor ya tiene un viaje en curso/aceptado, no se muestran ofertas.
                    const conductorOcupado = misViajes.some(t => ['accepted', 'in_progress'].includes(t.estado))

                    let disponibles = []
                    if (!conductorOcupado) {
                        try {
                            // Ofertas disponibles para aceptar. Se filtran las descartadas localmente.
                            const respuestaDisponibles = await axios.get('/api/conductor/viajes/available')
                            disponibles = (respuestaDisponibles.data || [])
                                .map(this.mapearViaje)
                                .filter(t => !this.idsOfertasIgnoradas.includes(t.id))
                        } catch (errorCapturado) {
                            console.error('❌ Error al obtener ofertas disponibles:', errorCapturado?.response?.data || errorCapturado?.message)
                            this.error = errorCapturado?.response?.data?.message || 'No se pudieron cargar las ofertas'
                        }
                    }

                    this.viajes = [...disponibles, ...misViajes]
                } else {
                    // Pasajero o admin: una lista simple desde el endpoint correspondiente.
                    const respuesta = await axios.get(endpoint)
                    console.log('✅ Respuesta de obtenerViajes:', respuesta.data)
                    this.viajes = respuesta.data.map(this.mapearViaje)
                }
                console.log('✅ Mapped viajes count:', this.viajes.length)
                console.log('✅ Viajes:', this.viajes)
            } catch (errorCapturado) {
                console.error('❌ Error al obtener viajes:', errorCapturado.response?.data || errorCapturado.message)
                this.error = errorCapturado.response?.data?.message || 'No se pudieron cargar los viajes'
            } finally {
                this.cargando = false
            }
        },

        async crearViaje(datosViaje) {
            // Crea un viaje desde los datos del mapa/form.
            this.cargando = true
            try {
                const respuesta = await axios.post('/api/viajes', {
                    pickup_lat: datosViaje.pickup_lat || datosViaje.pickupLat,
                    pickup_lng: datosViaje.pickup_lng || datosViaje.pickupLng,
                    dropoff_lat: datosViaje.dropoff_lat || datosViaje.dropoffLat,
                    dropoff_lng: datosViaje.dropoff_lng || datosViaje.dropoffLng,
                    pickup_address: datosViaje.pickup_address || datosViaje.pickup,
                    dropoff_address: datosViaje.dropoff_address || datosViaje.dropoff,
                    distance: datosViaje.distance,
                    scheduled_for: datosViaje.scheduled_for || null,
                    pasajeros: datosViaje.pasajeros || 1,
                    luggage: datosViaje.luggage || 0,
                    pago_method: datosViaje.pago_method || 'cash',
                    notes: datosViaje.notes || null,
                })

                const viajeNuevo = this.mapearViaje(respuesta.data)

                this.viajes.unshift(viajeNuevo)
                this.viajeActivo = viajeNuevo

                return { success: true, viaje: viajeNuevo }
            } catch (errorCapturado) {
                const mensaje = errorCapturado.response?.data?.message || 'No se pudo crear el viaje'
                this.error = mensaje

                return { success: false, error: mensaje }
            } finally {
                this.cargando = false
            }
        },

        async aceptarViaje(viajeId) {
            // Acepta un viaje (flujo conductor). Maneja 400/409 de forma amigable.
            try {
                const response = await axios.patch(`/api/viajes/${viajeId}/accept`)
                const viajeActualizado = this.mapearViaje(response.data)
                let replaced = false
                const updatedList = this.viajes
                    .filter(t => !(t.id === viajeId && t.estado === 'pendiente'))
                    .map(t => {
                        if (t.id !== viajeId) return t
                        replaced = true
                        return viajeActualizado
                    })

                this.viajes = replaced ? updatedList : [viajeActualizado, ...updatedList]
                this.viajeActivo = viajeActualizado

                const autenticacion = useAuthStore()
                const rutaActual = typeof window !== 'undefined' ? window.location.pathname : ''
                const asumirConductor = autenticacion.isconductor || rutaActual.startsWith('/conductor')
                if (asumirConductor) {
                    await this.obtenerViajes()
                }
            } catch (errorCapturado) {
                const status = errorCapturado.response?.status
                if (status === 400) {
                    this.error = errorCapturado.response?.data?.message || 'No se pudo aceptar el viaje.'
                    await this.obtenerViajes()

                    return
                }
                if (status === 409) {
                    this.error = 'Este viaje ya fue aceptado por otro conductor.'
                    await this.obtenerViajes()

                    return
                }
                throw errorCapturado
            }
        },

        descartarOferta(viajeId) {
            // Permite ocultar una oferta localmente sin avisar al servidor.
            if (!this.idsOfertasIgnoradas.includes(viajeId)) {
                this.idsOfertasIgnoradas.push(viajeId)
            }
            this.viajes = this.viajes.filter(t => t.id !== viajeId)
        },

        async iniciarViaje(viajeId) {
            // Marca el inicio del viaje.
            const response = await axios.patch(`/api/viajes/${viajeId}/start`)
            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = viajeActualizado

            const autenticacion = useAuthStore()
            const rutaActual = typeof window !== 'undefined' ? window.location.pathname : ''
            const asumirConductor = autenticacion.isconductor || rutaActual.startsWith('/conductor')
            if (asumirConductor) {
                await this.obtenerViajes()
            }
        },

        async completarViaje(viajeId, valoracion = null, comment = '') {
            // Completa el viaje y (opcionalmente) envía valoración/comentario.
            const response = await axios.patch(`/api/viajes/${viajeId}/complete`, {
                rating: valoracion,
                comment,
            })

            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = null

            const autenticacion = useAuthStore()
            const rutaActual = typeof window !== 'undefined' ? window.location.pathname : ''
            const asumirConductor = autenticacion.isconductor || rutaActual.startsWith('/conductor')
            if (asumirConductor) {
                await this.obtenerViajes()
            }
        },

        async cancelarViaje(viajeId) {
            // Cancela el viaje.
            const response = await axios.patch(`/api/viajes/${viajeId}/cancel`)
            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = null
        },

        simularMovimientoConductor(viajeId) {
            // Solo para demo/UI: simula progreso del conductor entre pickup y dropoff.
            const viaje = this.viajes.find(t => t.id === viajeId)
            if (!viaje) return

            let progress = 0
            const interval = setInterval(() => {
                progress += 0.1
                if (progress >= 1) {
                    clearInterval(interval)
                    this.ubicacionConductor = {
                        lat: viaje.dropoffLat,
                        lng: viaje.dropoffLng
                    }
                } else {
                    this.ubicacionConductor = {
                        lat: viaje.pickupLat + (viaje.dropoffLat - viaje.pickupLat) * progress,
                        lng: viaje.pickupLng + (viaje.dropoffLng - viaje.pickupLng) * progress
                    }
                }
            }, 2000)
        },

        async valorarViaje(viajeId, valoracion, comment = '') {
            // Envía una valoración posterior al viaje.
            const viaje = this.viajes.find(t => t.id === viajeId)
            if (!viaje) return

            const response = await axios.patch(`/api/viajes/${viajeId}/rate`, {
                rating: valoracion,
                comment,
            })

            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
        }
    }
})
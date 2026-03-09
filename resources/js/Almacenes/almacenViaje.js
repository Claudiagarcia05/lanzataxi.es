import { defineStore } from 'pinia'
import { useAuthStore } from './almacenAutenticacion'
import axios from 'axios'

export const useTripStore = defineStore('viaje', {
    state: () => ({
        viajes: [],
        viajeActivo: null,
        cargando: false,
        error: null,
        ubicacionConductor: null,
        pollingId: null,
        ignoredOfferIds: []
    }),

    getters: {
        viajesPasajero: (state) => {

            return state.viajes
        },
        viajesConductor: (state) => {
            const auth = useAuthStore()
            const userId = auth.usuario?.id
            if (userId == null) {

                return state.viajes.filter(t => t.conductorEntityId != null)
            }

            return state.viajes.filter(t => Number(t.conductorId) === Number(userId))
        },
        viajesPendientes: (state) => state.viajes.filter(t => t.estado === 'pendiente'),
        completedviajes: (state) => state.viajes.filter(t => t.estado === 'completed'),
        viajesHoy: (state) => {
            const today = new Date().toDateString()

            return state.viajes.filter(t => new Date(t.date).toDateString() === today)
        }
    },

    actions: {
        parseFiniteNumber(value) {
            const n = typeof value === 'number' ? value : parseFloat(value)
            
            return Number.isFinite(n) ? n : null
        },

        mapearViaje(viaje) {
            const auth = useAuthStore()

            const path = typeof window !== 'undefined' ? window.location.pathname : ''
            const isConductorContext = auth.isconductor || path.startsWith('/conductor')

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
                pickupLat: this.parseFiniteNumber(viaje.pickup_lat),
                pickupLng: this.parseFiniteNumber(viaje.pickup_lng),
                dropoffLat: this.parseFiniteNumber(viaje.dropoff_lat),
                dropoffLng: this.parseFiniteNumber(viaje.dropoff_lng),
                date: viaje.created_at,
                created_at: viaje.created_at,
                endTime: viaje.end_time,
                end_time: viaje.end_time,
                scheduled_for: viaje.scheduled_for,
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

        startPolling(intervalMs = 5000) {
            this.stopPolling()
            this.pollingId = setInterval(() => {
                this.fetchTrips()
            }, intervalMs)
        },

        stopPolling() {
            if (this.pollingId) {
                clearInterval(this.pollingId)
                this.pollingId = null
            }
        },

        async fetchTrips() {
            this.cargando = true
            this.error = null
            try {
                const auth = useAuthStore()
                let endpoint = '/api/user/viajes'

                const path = typeof window !== 'undefined' ? window.location.pathname : ''
                const assumeConductor = auth.isconductor || path.startsWith('/conductor')
                const assumeAdmin = auth.isAdmin || path.startsWith('/admin')

                if (assumeConductor) {
                    endpoint = '/api/conductor/viajes'
                } else if (assumeAdmin) {
                    endpoint = '/api/admin/viajes'
                }

                if (assumeConductor) {
                    const misViajesResponse = await axios.get(endpoint)
                    const misViajes = (misViajesResponse.data || []).map(this.mapearViaje)

                    const conductorOcupado = misViajes.some(t => ['accepted', 'in_progress'].includes(t.estado))

                    let disponibles = []
                    if (!conductorOcupado) {
                        try {
                            const disponiblesResponse = await axios.get('/api/conductor/viajes/available')
                            disponibles = (disponiblesResponse.data || [])
                                .map(this.mapearViaje)
                                .filter(t => !this.ignoredOfferIds.includes(t.id))
                        } catch (e) {
                            console.error('❌ Error fetching available trips:', e?.response?.data || e?.message)
                            this.error = e?.response?.data?.message || 'No se pudieron cargar las ofertas'
                        }
                    }

                    this.viajes = [...disponibles, ...misViajes]
                } else {
                    const response = await axios.get(endpoint)
                    console.log('✅ Response from fetchTrips:', response.data)
                    this.viajes = response.data.map(this.mapearViaje)
                }
                console.log('✅ Mapped viajes count:', this.viajes.length)
                console.log('✅ Viajes:', this.viajes)
            } catch (error) {
                console.error('❌ Error fetching trips:', error.response?.data || error.message)
                this.error = error.response?.data?.message || 'No se pudieron cargar los viajes'
            } finally {
                this.cargando = false
            }
        },

        async createTrip(datosViaje) {
            this.cargando = true
            try {
                const response = await axios.post('/api/viajes', {
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

                const viajeNuevo = this.mapearViaje(response.data)

                this.viajes.unshift(viajeNuevo)
                this.viajeActivo = viajeNuevo

                return { success: true, viaje: viajeNuevo }
            } catch (error) {
                const message = error.response?.data?.message || 'No se pudo crear el viaje'
                this.error = message

                return { success: false, error: message }
            } finally {
                this.cargando = false
            }
        },

        async aceptarViaje(viajeId) {
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

                const auth = useAuthStore()
                const path = typeof window !== 'undefined' ? window.location.pathname : ''
                const assumeConductor = auth.isconductor || path.startsWith('/conductor')
                if (assumeConductor) {
                    await this.fetchTrips()
                }
            } catch (error) {
                const status = error.response?.status
                if (status === 400) {
                    this.error = error.response?.data?.message || 'No se pudo aceptar el viaje.'
                    await this.fetchTrips()

                    return
                }
                if (status === 409) {
                    this.error = 'Este viaje ya fue aceptado por otro conductor.'
                    await this.fetchTrips()

                    return
                }
                throw error
            }
        },

        dismissOffer(viajeId) {
            if (!this.ignoredOfferIds.includes(viajeId)) {
                this.ignoredOfferIds.push(viajeId)
            }
            this.viajes = this.viajes.filter(t => t.id !== viajeId)
        },

        async startTrip(viajeId) {
            const response = await axios.patch(`/api/viajes/${viajeId}/start`)
            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = viajeActualizado

            const auth = useAuthStore()
            const path = typeof window !== 'undefined' ? window.location.pathname : ''
            const assumeConductor = auth.isconductor || path.startsWith('/conductor')
            if (assumeConductor) {
                await this.fetchTrips()
            }
        },

        async completeTrip(viajeId, valoracion = null, comment = '') {
            const response = await axios.patch(`/api/viajes/${viajeId}/complete`, {
                rating: valoracion,
                comment,
            })

            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = null

            const auth = useAuthStore()
            const path = typeof window !== 'undefined' ? window.location.pathname : ''
            const assumeConductor = auth.isconductor || path.startsWith('/conductor')
            if (assumeConductor) {
                await this.fetchTrips()
            }
        },

        async cancelTrip(viajeId) {
            const response = await axios.patch(`/api/viajes/${viajeId}/cancel`)
            const viajeActualizado = this.mapearViaje(response.data)
            this.viajes = this.viajes.map(t => t.id === viajeId ? viajeActualizado : t)
            this.viajeActivo = null
        },

        simularMovimientoConductor(viajeId) {
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

        async rateTrip(viajeId, valoracion, comment = '') {
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
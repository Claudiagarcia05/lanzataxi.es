import { defineStore } from 'pinia'
import { useAuthStore } from './almacenAutenticacion'
import { useViajeStore } from './almacenViaje'
import axios from 'axios'

/**
 * Store del conductor.
 *
 * Encapsula:
 * - Perfil del conductor (incluye taxi) y estado laboral (en línea / fuera de línea)
 * - Métricas de tiempo conectado (totales y por mes)
 * - Envío de ubicación al backend mientras está en línea
 */
export const useConductorStore = defineStore('conductor', {
  state: () => ({
    perfil: null,
    estaEnLinea: false,
    tiempoConectadoSegundos: 0,
    tiempoConectadoMesSegundos: 0,
    enLineaDesde: null,
    mesEnLinea: null,
    estadoActualizadoEnMs: null,
    estadisticas: {
      viajesHoy: 0,
      gananciasHoy: 0,
      gananciasSemanales: 0,
      gananciasMensuales: 0,
      valoracion: 4.92,
      totalViajes: 156
    },
    ubicacionActual: null,
    observadorUbicacion: null,
    cargando: false
  }),

  getters: {
    valoracionFormateada: (state) => state.estadisticas.valoracion.toFixed(2),
    gananciasHoyFormateadas: (state) => `${state.estadisticas.gananciasHoy.toFixed(2)} €`,
    estaDisponible: (state) => state.estaEnLinea && state.perfil?.verificado,
    horasConectado: (state) => (Number(state.tiempoConectadoMesSegundos || 0) / 3600),
  },

  actions: {
    async obtenerEstadoConductor() {
      // Obtiene estado laboral y métricas básicas.
      const respuestaEstado = await axios.get('/api/conductor/status')

      this.estaEnLinea = Boolean(respuestaEstado.data.is_active)
      this.estadisticas = {
        ...this.estadisticas,
        valoracion: Number(respuestaEstado.data.rating || this.estadisticas.valoracion),
      }
      this.tiempoConectadoSegundos = Number(respuestaEstado.data.connected_seconds || 0)
      this.tiempoConectadoMesSegundos = Number(respuestaEstado.data.connected_seconds_month || 0)
      this.mesEnLinea = respuestaEstado.data.online_month || null
      this.enLineaDesde = respuestaEstado.data.online_since || null
      this.estadoActualizadoEnMs = Date.now()

      return respuestaEstado.data
    },

    async obtenerPerfilConductor() {
      // Carga perfil + estado en paralelo y normaliza placeholders de taxi.
      this.cargando = true
      try {
        const [respuestaPerfil, respuestaEstado] = await Promise.all([
          axios.get('/api/conductor/profile'),
          axios.get('/api/conductor/status'),
        ])

        const auth = useAuthStore()
        const datosPerfil = respuestaPerfil.data
        const taxiRaw = datosPerfil.taxi || {}
        const plateRaw = String(taxiRaw.plate || '')
        const modelRaw = String(taxiRaw.model || '')
        const esPlatePlaceholder = plateRaw.startsWith('PENDIENTE-') || plateRaw.startsWith('TMP-')
        const esModelPlaceholder = modelRaw.trim().toLowerCase() === 'pendiente'

        const taxi = {
          ...taxiRaw,
          plate: esPlatePlaceholder ? '' : plateRaw,
          model: esModelPlaceholder ? '' : modelRaw,
        }

        let capacidad = taxi.capacity ?? null
        if ((esPlatePlaceholder || esModelPlaceholder) && Number(capacidad || 0) === 4) {
          capacidad = null
        }
        const avatar = datosPerfil.user?.avatar || null

        if (datosPerfil.user) {
          // Mantiene el store de auth sincronizado (nombre/email/teléfono/avatar).
          auth.usuario = {
            ...(auth.usuario || {}),
            name: datosPerfil.user.name,
            email: datosPerfil.user.email,
            phone: datosPerfil.user.phone,
            avatar: datosPerfil.user.avatar
          }
          localStorage.setItem('usuario', JSON.stringify(auth.usuario))
        }

        this.perfil = {
          id: auth.usuario?.id,
          name: datosPerfil.user?.name || auth.usuario?.name,
          email: datosPerfil.user?.email || auth.usuario?.email,
          phone: datosPerfil.user?.phone || '',
          avatar: avatar,
          numeroLicencia: datosPerfil.license_number,
          vehiculo: {
            modelo: String(taxi.model || ''),
            matricula: String(taxi.plate || ''),
            anio: taxi.year || new Date().getFullYear(),
            color: taxi.color || 'Sin color',
            capacidad: capacidad
          },
          verificado: true,
          fechaRegistro: datosPerfil.created_at?.split('T')[0] || null
        }

        this.estaEnLinea = Boolean(respuestaEstado.data.is_active)
        this.estadisticas = {
          ...this.estadisticas,
          valoracion: Number(respuestaEstado.data.rating || this.estadisticas.valoracion),
        }

        this.tiempoConectadoSegundos = Number(respuestaEstado.data.connected_seconds || 0)
        this.tiempoConectadoMesSegundos = Number(respuestaEstado.data.connected_seconds_month || 0)
        this.mesEnLinea = respuestaEstado.data.online_month || null
        this.enLineaDesde = respuestaEstado.data.online_since || null
        this.estadoActualizadoEnMs = Date.now()
      } catch (error) {
        console.error('Error al obtener perfil de conductor:', error)
      } finally {
        this.cargando = false
      }
    },

    cambiarEstadoEnLinea() {
      // Toggle del estado de disponibilidad.
      this.establecerEstadoEnLinea(!this.estaEnLinea)
    },

    async establecerEstadoEnLinea(valor) {
      try {
        // Actualización optimista; si falla, se revertirá vía la respuesta/throw.
        this.estaEnLinea = valor
        const respuesta = await axios.patch('/api/conductor/status', { is_active: valor })

        if (respuesta?.data) {
          this.estaEnLinea = Boolean(respuesta.data.is_active)
          if (respuesta.data.connected_seconds != null) {
            this.tiempoConectadoSegundos = Number(respuesta.data.connected_seconds || 0)
          }
          if (respuesta.data.connected_seconds_month != null) {
            this.tiempoConectadoMesSegundos = Number(respuesta.data.connected_seconds_month || 0)
          }
          if (respuesta.data.online_month !== undefined) {
            this.mesEnLinea = respuesta.data.online_month || null
          }
          if (respuesta.data.online_since !== undefined) {
            this.enLineaDesde = respuesta.data.online_since || null
          }

          this.estadoActualizadoEnMs = Date.now()
        }
        if (this.estaEnLinea) {
          // Al ponerse en línea, inicia el seguimiento de ubicación.
          this.iniciarSeguimientoUbicacion()
        } else {
          this.detenerSeguimientoUbicacion()
        }
      } catch (error) {
        console.error('Error al cambiar el estado laboral:', error)
        throw error
      }
    },

    iniciarSeguimientoUbicacion() {
      // Envía coordenadas periódicamente usando `watchPosition`.
      // Nota: el backend debe tolerar pérdidas/intermitencias.
      if (navigator.geolocation) {
        this.observadorUbicacion = navigator.geolocation.watchPosition(
          (position) => {
            this.ubicacionActual = {
              lat: position.coords.latitude,
              lng: position.coords.longitude
            }

            axios.post('/api/conductor/ubicacion', {
              lat: this.ubicacionActual.lat,
              lng: this.ubicacionActual.lng,
            }).catch(() => {})
          },
            (error) => console.error('Error al obtener ubicacion:', error),
          { enableHighAccuracy: true, timeout: 10000 }
        )
      }
    },

    detenerSeguimientoUbicacion() {
      // Detiene el watch de geolocalización.
      if (this.observadorUbicacion) {
        navigator.geolocation.clearWatch(this.observadorUbicacion)
        this.observadorUbicacion = null
      }
    },

    async actualizarEstadisticas() {
      // Calcula estadísticas en base al store de viajes ya cargado.
      const storeViaje = useViajeStore()
      const auth = useAuthStore()
      const idUsuarioConductor = this.perfil?.id || auth.usuario?.id
      const viajesHoy = storeViaje.viajesHoy.filter(t => t.conductorId === idUsuarioConductor)
      const gananciasHoy = viajesHoy.reduce((suma, viaje) => suma + (viaje.price || 0), 0)

      this.estadisticas = {
        ...this.estadisticas,
        viajesHoy: viajesHoy.length,
        gananciasHoy,
        totalViajes: storeViaje.viajesConductor.length,
      }
    },

    async aceptarViaje(viajeId) {
      // Delegación a store de viajes y luego refresco de métricas.
      const storeViaje = useViajeStore()
      await storeViaje.aceptarViaje(viajeId)
      this.actualizarEstadisticas()
    },
  }
})
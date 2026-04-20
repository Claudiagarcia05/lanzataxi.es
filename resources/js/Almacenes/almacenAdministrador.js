import { defineStore } from 'pinia'
import axios from 'axios'

/**
 * Store de administración.
 *
 * Centraliza la carga/gestión de datos para el panel admin:
 * - usuarios, conductores, taxis
 * - estadísticas globales y mensuales
 * - aprobación/rechazo de conductores
 *
 * Importante: este store asume endpoints bajo `/api/admin/*` y algunos
 * endpoints compartidos (`/api/taxis`, `/api/conductors`).
 */
export const useAdminStore = defineStore('admin', {
  state: () => ({
    usuarios: [],
    conductores: [],
    taxis: [],
    conductoresPendientes: [],
    estadisticas: {
      totalUsuarios: 0,
      conductoresActivos: 0,
      totalTaxis: 0,
      viajesHoy: 0,
      ingresosHoy: 0,
      valoracionMedia: 0,
    },
    estadisticasMensuales: {
      anio: new Date().getFullYear(),
      mes: new Date().getMonth() + 1,
      viajesCompletados: 0,
      viajesCancelados: 0,
      ingresos: 0,
      anioMinimo: new Date().getFullYear(),
      anioMaximo: new Date().getFullYear(),
      diario: null,
    },
    modalPendientesAbierto: false,
    pendientesNuevos: [],
    pendientesVistosIds: [],
    cargando: false
  }),

  getters: {
    totalpasajeros: (state) => state.usuarios.filter(u => u.role === 'pasajero').length,
    totalconductores: (state) => state.conductores.length,
    taxisActivos: (state) => state.taxis.filter(t => t.estado === 'activo').length,
    solicitudesPendientes: (state) => state.conductoresPendientes.length,
    ingresosHoyFormateado: (state) => `${Number(state.estadisticas.ingresosHoy || 0).toFixed(2)} €`,
    ingresosMensualesFormateado: (state) => `${Number(state.estadisticasMensuales.ingresos || 0).toFixed(2)} €`,
  },

  actions: {
    /**
     * Sanitiza la entidad taxi antes de mostrarla en formularios de edición.
     *
     * Objetivo: cuando el backend crea un taxi placeholder (p.ej. `PENDIENTE-*`),
     * se prefiere mostrar campos vacíos para forzar al admin a completarlos.
     */
    _sanearTaxiParaEdicion(taxi) {
      if (!taxi) return null

      const plate = String(taxi.plate || '')
      const esPlatePlaceholder = plate.startsWith('PENDIENTE-') || plate.startsWith('TMP-')
      const model = String(taxi.model || '')
      const esModelPlaceholder = model.trim().toLowerCase() === 'pendiente'

      const taxiSan = { ...taxi }

      if (esPlatePlaceholder) taxiSan.plate = ''
      if (esModelPlaceholder) taxiSan.model = ''

      if ((esPlatePlaceholder || esModelPlaceholder) && Number(taxiSan.capacity || 0) === 4) {
        taxiSan.capacity = null
      }

      return taxiSan
    },

    async obtenerTodosLosDatos() {
      // Carga en paralelo todos los bloques necesarios para el dashboard admin.
      this.cargando = true
      try {
        await Promise.all([
          this.obtenerUsuarios(),
          this.obtenerConductores(),
          this.obtenerTaxis(),
          this.obtenerConductoresPendientes({ abrirModalSiHayNuevos: false }),
          this.obtenerEstadisticas(),
          this.obtenerEstadisticasMensuales({
            anio: this.estadisticasMensuales.anio,
            mes: this.estadisticasMensuales.mes,
          }),
        ])
      } catch (error) {
        console.error('Error al obtener datos de admin:', error)
      } finally {
        this.cargando = false
      }
    },

    async obtenerUsuarios() {
      // Lista de usuarios (para tabla admin).
      const response = await axios.get('/api/admin/users')
      this.usuarios = response.data.map(usuario => ({
        id: usuario.id,
        name: usuario.name,
        email: usuario.email,
        role: usuario.role,
        is_disabled: Boolean(usuario.is_disabled),
        estado: usuario.is_disabled ? 'inactivo' : 'activo',
        fechaRegistro: usuario.created_at?.split('T')[0],
        fechaBaja: usuario.disabled_at?.split('T')[0] || null,
        phone: usuario.phone,
      }))
    },

    async obtenerConductores() {
      // Lista conductores incluyendo usuario, taxi y estado de aprobación.
      const response = await axios.get('/api/conductors')
      this.conductores = response.data.map(conductor => ({
        id: conductor.id,
        user_id: conductor.user_id,
        name: conductor.user?.name || 'Sin nombre',
        email: conductor.user?.email || '',
        phone: conductor.user?.phone || '',
        taxi_status: conductor.taxi?.status ?? null,
        is_active: Boolean(conductor.is_active),
        is_disabled: Boolean(conductor.user?.is_disabled),
        approval_status: conductor.approval_status,
        approved_at: conductor.approved_at,
        rejected_at: conductor.rejected_at,
        license_number: conductor.license_number,
        vehiculo: conductor.taxi
          ? (() => {
              const taxiSan = this._sanearTaxiParaEdicion(conductor.taxi)
              const modelo = String(taxiSan?.model || '').trim()
              const matricula = String(taxiSan?.plate || '').trim()
              const etiqueta = [modelo, matricula].filter(Boolean).join(' · ')

              return etiqueta || ''
            })()
          : '',
        estado: conductor.is_active ? 'activo' : 'inactivo',
        valoracion: Number(conductor.rating || 0),
        viajes: conductor.viajes_count || 0,
        taxi: this._sanearTaxiParaEdicion(conductor.taxi) || null,
      }))
    },

    async obtenerTaxis() {
      // Lista taxis existentes.
      const response = await axios.get('/api/taxis')
      this.taxis = response.data.map(taxi => ({
        id: taxi.id,
        plate: taxi.plate,
        model: taxi.model,
        conductor: taxi.conductor?.user?.name || 'Sin asignar',
        estado: taxi.status === 'available' ? 'activo' : taxi.status,
        year: taxi.year || null,
      }))
    },

    async obtenerConductoresPendientes({ abrirModalSiHayNuevos } = { abrirModalSiHayNuevos: true }) {
      // Obtiene solicitudes pendientes de alta/aprobación de conductor.
      const response = await axios.get('/api/admin/pending-conductors')

      const pendientes = response.data.map(conductor => ({
        id: conductor.id,
        user_id: conductor.user_id,
        name: conductor.user?.name || 'Sin nombre',
        email: conductor.user?.email || '',
        phone: conductor.user?.phone || '',
        is_disabled: Boolean(conductor.user?.is_disabled),
        license_number: conductor.license_number,
        created_at: conductor.created_at,
        taxi: this._sanearTaxiParaEdicion(conductor.taxi) || null,
      }))

      const vistos = Array.isArray(this.pendientesVistosIds) ? this.pendientesVistosIds : []
      const nuevos = pendientes.filter(p => !vistos.includes(p.id))
      this.conductoresPendientes = pendientes
      this.pendientesVistosIds = [...new Set([...vistos, ...pendientes.map(p => p.id)])]

      if (abrirModalSiHayNuevos && nuevos.length > 0) {
        this.pendientesNuevos = nuevos
        this.modalPendientesAbierto = true
      }
    },

    async obtenerEstadisticas() {
      // Estadísticas generales del día.
      const response = await axios.get('/api/admin/stats')
      this.estadisticas = {
        totalUsuarios: response.data.totalUsers,
        conductoresActivos: response.data.activeConductors,
        totalTaxis: response.data.totalTaxis,
        viajesHoy: response.data.todayTrips,
        ingresosHoy: response.data.todayRevenue,
        valoracionMedia: response.data.averageRating,
      }
    },

    async obtenerEstadisticasMensuales({ anio, mes } = {}) {
      // Estadísticas agregadas por mes (incluye serie diaria para gráficas).
      const params = {
        year: anio ?? this.estadisticasMensuales.anio,
        month: mes ?? this.estadisticasMensuales.mes,
      }
      const response = await axios.get('/api/admin/monthly-stats', { params })

      const diarioApi = response.data.daily || null
      this.estadisticasMensuales = {
        anio: response.data.year,
        mes: response.data.month,
        viajesCompletados: response.data.completedTrips,
        viajesCancelados: response.data.cancelledTrips,
        ingresos: response.data.revenue,
        anioMinimo: response.data.minYear,
        anioMaximo: response.data.maxYear,
        diario: diarioApi
          ? {
              etiquetas: diarioApi.labels || [],
              viajesCompletados: diarioApi.completedTrips || [],
              viajesCancelados: diarioApi.cancelledTrips || [],
              ingresos: diarioApi.revenue || [],
            }
          : null,
      }
    },

    async aprobarConductor(conductorId) {
      // Aprueba y recarga listados relevantes.
      await axios.post(`/api/admin/conductors/${conductorId}/approve`)
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
      this.pendientesNuevos = this.pendientesNuevos.filter(d => d.id !== conductorId)
      await Promise.all([this.obtenerConductores(), this.obtenerConductoresPendientes({ abrirModalSiHayNuevos: false })])
    },

    async rechazarConductor(conductorId) {
      // Rechaza y recarga pendientes.
      await axios.post(`/api/admin/conductors/${conductorId}/reject`)
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
      this.pendientesNuevos = this.pendientesNuevos.filter(d => d.id !== conductorId)
      await this.obtenerConductoresPendientes({ abrirModalSiHayNuevos: false })
    },

		async darDeBajaUsuario(userId) {
      // Deshabilita usuario (baja lógica) y refresca tablas.
			const response = await axios.patch(`/api/admin/users/${userId}/disable`)
			const isDisabled = Boolean(response?.data?.is_disabled)
			if (!isDisabled) {
				throw new Error('El servidor no confirmó la baja (is_disabled=false).')
			}

			await Promise.all([this.obtenerUsuarios(), this.obtenerConductores()])
    },

		cerrarModalPendientes() {
			// Cierra modal y limpia los “nuevos” para no reabrir en bucle.
      this.modalPendientesAbierto = false
      this.pendientesNuevos = []
    },

    async crearAdmin(payload) {
      // Crea un nuevo admin (requiere permisos) y recarga usuarios.
      const response = await axios.post('/api/admin/admins', payload)
      await this.obtenerUsuarios()
      
      return response.data
    },

    async actualizarEstadoUsuario(usuarioId, estado) {
      const usuario = this.usuarios.find(u => u.id === usuarioId)
      if (usuario) {
        usuario.estado = estado
      }
    },

    async actualizarEstadoTaxi(taxiId, estado) {
      // Normaliza el estado de UI a estado esperado por el backend.
      const estadoMapeado = estado === 'activo' ? 'available' : estado
      await axios.put(`/api/taxis/${taxiId}`, { status: estadoMapeado })
      const taxi = this.taxis.find(t => t.id === taxiId)
      if (taxi) taxi.estado = estado
    }
  }
})
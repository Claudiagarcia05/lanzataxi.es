import { defineStore } from 'pinia'
import axios from 'axios'

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
    async obtenerTodosLosDatos() {
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
      const response = await axios.get('/api/conductors')
      this.conductores = response.data.map(conductor => ({
        id: conductor.id,
        user_id: conductor.user_id,
        name: conductor.user?.name || 'Sin nombre',
        email: conductor.user?.email || '',
        phone: conductor.user?.phone || '',
        is_disabled: Boolean(conductor.user?.is_disabled),
        approval_status: conductor.approval_status,
        approved_at: conductor.approved_at,
        rejected_at: conductor.rejected_at,
        license_number: conductor.license_number,
        vehiculo: conductor.taxi ? `${conductor.taxi.model} - ${conductor.taxi.plate}` : 'Sin taxi',
        estado: conductor.is_active ? 'activo' : 'inactivo',
        valoracion: Number(conductor.rating || 0),
        viajes: conductor.viajes_count || 0,
        taxi: conductor.taxi || null,
      }))
    },

    async obtenerTaxis() {
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
        taxi: conductor.taxi || null,
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
      await axios.post(`/api/admin/conductors/${conductorId}/approve`)
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
      this.pendientesNuevos = this.pendientesNuevos.filter(d => d.id !== conductorId)
      await Promise.all([this.obtenerConductores(), this.obtenerConductoresPendientes({ abrirModalSiHayNuevos: false })])
    },

    async rechazarConductor(conductorId) {
      await axios.post(`/api/admin/conductors/${conductorId}/reject`)
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
      this.pendientesNuevos = this.pendientesNuevos.filter(d => d.id !== conductorId)
      await this.obtenerConductoresPendientes({ abrirModalSiHayNuevos: false })
    },

		async darDeBajaUsuario(userId) {
      await axios.patch(`/api/admin/users/${userId}/disable`)
      const usuario = this.usuarios.find(u => u.id === userId)
      if (usuario) {
        usuario.is_disabled = true
        usuario.estado = 'inactivo'
        if (!usuario.fechaBaja) {
          usuario.fechaBaja = new Date().toISOString().split('T')[0]
        }
      }
      const conductor = this.conductores.find(c => c.user_id === userId)
      if (conductor) conductor.is_disabled = true
    },

		cerrarModalPendientes() {
      this.modalPendientesAbierto = false
      this.pendientesNuevos = []
    },

    async crearAdmin(payload) {
      const response = await axios.post('/api/admin/admins', payload)
      // Refrescar usuarios para que el panel refleje el alta.
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
      const estadoMapeado = estado === 'activo' ? 'available' : estado
      await axios.put(`/api/taxis/${taxiId}`, { status: estadoMapeado })
      const taxi = this.taxis.find(t => t.id === taxiId)
      if (taxi) taxi.estado = estado
    }
  }
})
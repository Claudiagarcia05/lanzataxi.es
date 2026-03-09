import { defineStore } from 'pinia'
import axios from 'axios'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    usuarios: [],
    conductores: [],
    taxis: [],
    conductoresPendientes: [],
    estadisticas: {
      totalUsuarios: 2547,
      conductoresActivos: 89,
      totalTaxis: 150,
      viajesHoy: 342,
      ingresosHoy: 5234,
      valoracionMedia: 4.7
    },
    cargando: false
  }),

  getters: {
    totalpasajeros: (state) => state.usuarios.filter(u => u.role === 'pasajero').length,
    totalconductores: (state) => state.conductores.length,
    taxisActivos: (state) => state.taxis.filter(t => t.estado === 'activo').length,
    solicitudesPendientes: (state) => state.conductoresPendientes.length,
    ingresosHoyFormateado: (state) => `${state.estadisticas.ingresosHoy.toFixed(2)} €`
  },

  actions: {
    async fetchAllData() {
      this.cargando = true
      try {
        await Promise.all([
          this.obtenerUsuarios(),
          this.obtenerConductores(),
          this.obtenerTaxis(),
          this.obtenerConductoresPendientes(),
          this.obtenerEstadisticas()
        ])
      } catch (error) {
        console.error('Error fetching admin data:', error)
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
        estado: 'activo',
        fechaRegistro: usuario.created_at?.split('T')[0],
        phone: usuario.phone,
      }))
    },

    async obtenerConductores() {
      const response = await axios.get('/api/conductors')
      this.conductores = response.data.map(conductor => ({
        id: conductor.id,
        name: conductor.user?.name || 'Sin nombre',
        email: conductor.user?.email || '',
        phone: conductor.user?.phone || '',
        vehiculo: conductor.taxi ? `${conductor.taxi.model} - ${conductor.taxi.plate}` : 'Sin taxi',
        estado: conductor.is_active ? 'activo' : 'inactive',
        valoracion: Number(conductor.rating || 0),
        viajes: conductor.viajes_count || 0,
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

    async obtenerConductoresPendientes() {
      const response = await axios.get('/api/admin/pending-conductors')
      this.conductoresPendientes = response.data.map(conductor => ({
        id: conductor.id,
        name: conductor.user?.name || 'Sin nombre',
        email: conductor.user?.email || '',
        phone: conductor.user?.phone || '',
        licencia: conductor.license_number,
        fechaSolicitud: conductor.created_at?.split('T')[0],
      }))
    },

    async obtenerEstadisticas() {
      const response = await axios.get('/api/admin/stats')
      this.estadisticas = {
        totalUsuarios: response.data.totalUsers,
        conductoresActivos: response.data.activeDrivers,
        totalTaxis: response.data.totalTaxis,
        viajesHoy: response.data.todayTrips,
        ingresosHoy: response.data.todayRevenue,
        valoracionMedia: response.data.averageRating,
      }
    },

    async aprobarConductor(conductorId) {
      await axios.put(`/api/conductors/${conductorId}`, { is_active: true })
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
      await this.obtenerConductores()
    },

    async rechazarConductor(conductorId) {
      await axios.delete(`/api/conductors/${conductorId}`)
      this.conductoresPendientes = this.conductoresPendientes.filter(d => d.id !== conductorId)
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
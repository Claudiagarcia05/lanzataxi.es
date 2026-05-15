import { defineStore } from 'pinia'
import axios from 'axios'

/**
 * Store de cartera (wallet).
 *
 * Gestiona saldo, transacciones y deuda pendiente del usuario.
 * Endpoints esperados:
 * - GET  `/api/wallet/balance`
 * - GET  `/api/wallet/transactions`
 * - GET  `/api/wallet/debts`
 * - POST `/api/wallet/add`
 * - POST `/api/wallet/withdraw`
 * - POST `/api/wallet/use`
 */
export const useCarteraStore = defineStore('cartera', {
  state: () => ({
    saldo: 0,
    transacciones: [],
    cargando: false,
    deudaPendiente: 0
  }),

  getters: {
    saldoFormateado: (state) => {

      return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(state.saldo)
    }
  },

  actions: {
    async obtenerSaldo() {
      // Recupera el saldo actual.
      try {
        const respuesta = await axios.get('/api/wallet/balance')
        this.saldo = parseFloat(respuesta.data.balance) || 0
      } catch (errorCapturado) {
        console.error('Error al obtener saldo de cartera:', errorCapturado)
      }
    },

    async obtenerResumenDeuda() {
      // Recupera deuda pendiente agregada (si existe).
      try {
        const respuesta = await axios.get('/api/wallet/debts')
        this.deudaPendiente = parseFloat(respuesta.data.pending_debt) || 0
      } catch (errorCapturado) {
        console.error('Error al obtener resumen de deuda:', errorCapturado)
      }
    },

    async obtenerTransacciones() {
      // Lista transacciones y normaliza `amount` a número.
      try {
        const respuesta = await axios.get('/api/wallet/transactions')
        this.transacciones = respuesta.data.map((transaccion) => ({
          ...transaccion,
          amount: parseFloat(transaccion.amount)
        }))
      } catch (errorCapturado) {
        console.error('Error al obtener transacciones:', errorCapturado)
      }
    },

    async anadirFondos(monto) {
      // Añade saldo a la cartera.
      this.cargando = true
      try {
        const respuesta = await axios.post('/api/wallet/add', { 
          amount: parseFloat(monto) 
        })
        this.saldo = parseFloat(respuesta.data.new_balance)
        this.transacciones.unshift({
          ...respuesta.data.transaction,
          amount: parseFloat(respuesta.data.transaction.amount)
        })

        return { success: true }
      } catch (errorCapturado) {

        return { 
          success: false, 
          error: errorCapturado.response?.data?.message || 'Error al anadir fondos' 
        }
      } finally {
        this.cargando = false
      }
    },

    async retirarFondos(monto, datosBancarios = {}) {
      // Retira saldo (si el backend lo permite), enviando los datos bancarios del destinatario.
      try {
        const respuesta = await axios.post('/api/wallet/withdraw', {
          amount: parseFloat(monto),
          ...datosBancarios
        })
        this.saldo = parseFloat(respuesta.data.new_balance)
        this.transacciones.unshift({
          ...respuesta.data.transaction,
          amount: parseFloat(respuesta.data.transaction.amount)
        })

        return { success: true }
      } catch (errorCapturado) {

        return { 
          success: false, 
          error: errorCapturado.response?.data?.message || 'Error al retirar fondos' 
        }
      }
    },

    async usarFondos(monto, viajeId) {
      // Usa fondos para un viaje concreto (p.ej. para pagar/cubrir deuda).
      try {
        const respuesta = await axios.post('/api/wallet/use', { 
          amount: parseFloat(monto), 
          viaje_id: viajeId 
        })
        this.saldo = parseFloat(respuesta.data.new_balance)

        return { success: true }
      } catch (errorCapturado) {

        return { 
          success: false, 
          error: errorCapturado.response?.data?.message || 'Error al usar fondos' 
        }
      }
    }
  }
})
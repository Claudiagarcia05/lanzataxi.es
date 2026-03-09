import { defineStore } from 'pinia'
import axios from 'axios'

export const useWalletStore = defineStore('wallet', {
  state: () => ({
    balance: 0,
    transactions: [],
    cargando: false,
    pendingDebt: 0
  }),

  getters: {
    formattedBalance: (state) => {

      return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(state.balance)
    }
  },

  actions: {
    async fetchBalance() {
      try {
        const response = await axios.get('/api/wallet/balance')
        this.balance = parseFloat(response.data.balance) || 0
      } catch (error) {
        console.error('Error fetching wallet balance:', error)
      }
    },

    async fetchDebtSummary() {
      try {
        const response = await axios.get('/api/wallet/debts')
        this.pendingDebt = parseFloat(response.data.pending_debt) || 0
      } catch (error) {
        console.error('Error fetching debt summary:', error)
      }
    },

    async fetchTransactions() {
      try {
        const response = await axios.get('/api/wallet/transactions')
        this.transactions = response.data.map(t => ({
          ...t,
          amount: parseFloat(t.amount)
        }))
      } catch (error) {
        console.error('Error fetching transactions:', error)
      }
    },

    async addFunds(amount) {
      this.cargando = true
      try {
        const response = await axios.post('/api/wallet/add', { 
          amount: parseFloat(amount) 
        })
        this.balance = parseFloat(response.data.new_balance)
        this.transactions.unshift({
          ...response.data.transaction,
          amount: parseFloat(response.data.transaction.amount)
        })

        return { success: true }
      } catch (error) {

        return { 
          success: false, 
          error: error.response?.data?.message || 'Error al añadir fondos' 
        }
      } finally {
        this.cargando = false
      }
    },

    async withdrawFunds(amount) {
      try {
        const response = await axios.post('/api/wallet/withdraw', { 
          amount: parseFloat(amount) 
        })
        this.balance = parseFloat(response.data.new_balance)
        this.transactions.unshift({
          ...response.data.transaction,
          amount: parseFloat(response.data.transaction.amount)
        })

        return { success: true }
      } catch (error) {

        return { 
          success: false, 
          error: error.response?.data?.message || 'Error al retirar fondos' 
        }
      }
    },

    async useFunds(amount, viajeId) {
      try {
        const response = await axios.post('/api/wallet/use', { 
          amount: parseFloat(amount), 
          viaje_id: viajeId 
        })
        this.balance = parseFloat(response.data.new_balance)

        return { success: true }
      } catch (error) {

        return { 
          success: false, 
          error: error.response?.data?.message || 'Error al usar fondos' 
        }
      }
    }
  }
})
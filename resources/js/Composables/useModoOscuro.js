import { ref } from 'vue'

const CLAVE = 'lanzataxi_dark_mode'
const modoOscuro = ref(false)

function aplicar() {
  if (modoOscuro.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

export function useModoOscuro() {
  const inicializar = () => {
    const guardado = localStorage.getItem(CLAVE)
    const prefereDark = typeof window !== 'undefined' &&
      window.matchMedia &&
      window.matchMedia('(prefers-color-scheme: dark)').matches
    modoOscuro.value = guardado === 'true' || (guardado === null && prefereDark)
    aplicar()
  }

  const alternarModoOscuro = () => {
    modoOscuro.value = !modoOscuro.value
    localStorage.setItem(CLAVE, String(modoOscuro.value))
    aplicar()
  }

  return { modoOscuro, inicializar, alternarModoOscuro }
}

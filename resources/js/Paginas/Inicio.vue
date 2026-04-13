<template>
  <!-- Página de Inicio (Landing) -->
  <div class="min-h-screen bg-neutral-soft">
    <!-- Barra de navegación superior (logo + enlaces) -->
    <BarraNavegacion />

    <!-- Contenido principal; el enlace "Saltar al contenido" apunta aquí -->
    <main id="main-content">
      <!-- Hero: propuesta de valor + CTA -->
      <section class="pt-20 bg-lanzarote-blue text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
          <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h1 class="text-4xl md:text-5xl font-bold mb-6">
                Tu taxi en <span class="text-lanzarote-yellow">Lanzarote</span>
              </h1>
              <p class="text-xl text-white/90 mb-8 leading-relaxed">
                Reserva tu taxi con seguimiento en tiempo real. Llega a cualquier municipio de la isla de forma segura, rápida y con precios cerrados.
              </p>
              <div class="flex flex-wrap gap-4">
                <!-- CTA: abre modal de autenticación para reservar -->
                <button @click="showAuthModal = true" class="bg-lanzarote-yellow text-black px-8 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all shadow-lg">
                  Reservar ahora
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

    <!-- Sección: Cómo funciona -->
    <section id="como-funciona-section" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-neutral-dark mb-4">
            Cómo funciona <span class="text-lanzarote-blue">LanzaTaxi</span>
          </h2>
          <p class="text-xl text-neutral-slate max-w-3xl mx-auto">
            Reservar tu taxi es muy sencillo. Sigue estos 3 pasos y estarás en tu destino.
          </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <!-- Pasos (contenido estático definido en el script) -->
          <div v-for="paso in pasosReserva" :key="paso.numero" class="text-center relative">
            <div class="text-8xl font-bold text-neutral-volcanic/30 absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/4">
              {{ paso.numero }}
            </div>
            <div class="relative pt-12">
              <div class="w-20 h-20 bg-lanzarote-blue/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="text-3xl text-lanzarote-blue">{{ paso.numero }}</span>
              </div>
              <h3 class="text-2xl font-bold text-neutral-dark mb-3">{{ paso.titulo }}</h3>
              <p class="text-neutral-slate">{{ paso.descripcion }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: Municipios / teléfonos (enlaces tel:) -->
    <section id="municipios-section" class="py-20 bg-neutral-soft">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-neutral-dark mb-4">
            Los 7 municipios de <span class="text-lanzarote-blue">Lanzarote</span>
          </h2>
          <p class="text-xl text-neutral-slate max-w-3xl mx-auto">
            Operamos en toda la isla. Selecciona tu municipio y te recogemos donde estés.
          </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="municipio in municipios" :key="municipio.nombre"
               class="group bg-white rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-neutral-volcanic">
            <div class="h-2 bg-lanzarote-blue"></div>
            <div class="p-8">
              <h3 class="text-2xl font-bold text-neutral-dark mb-2">{{ municipio.nombre }}</h3>
              <p class="text-neutral-slate mb-4">{{ municipio.desc }}</p>
              <div>
                <span class="text-sm font-medium text-neutral-dark mb-2 block">Radio taxi</span>
                <div class="space-y-2">
                  <!-- Se genera un enlace de llamada por cada teléfono del municipio -->
                  <a v-for="telefono in municipio.telefonos" :key="telefono" :href="`tel:${telefono.replace(/\s/g, '')}`" class="flex items-center space-x-2 text-lanzarote-blue hover:text-lanzarote-blue/80 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <span class="font-medium">{{ telefono }}</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: Opiniones / testimonios -->
    <section id="testimonios-section" class="py-20 bg-lanzarote-yellow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center mb-16">
          <div class="flex items-center gap-2 mb-2">
            <span class="font-bold text-neutral-dark text-sm uppercase tracking-widest">EXCELENTE</span>
            <div class="flex text-orange-500 text-lg">
              <template v-for="i in 5">★</template>
            </div>
            <span class="text-neutral-dark font-medium ml-1">{{ testimonios.length }} reseñas</span>
            <!-- Logo externo de Google (reputación / reseñas) -->
            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google" class="h-5 ml-1">
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Tarjeta por testimonio (contenido definido en el script) -->
          <div v-for="testimonio in testimonios" :key="testimonio.nombre" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-fit">
            <div class="flex justify-between items-start mb-1">
              <h4 class="font-bold text-gray-900 text-[15px] leading-tight">{{ testimonio.nombre }}</h4>
              <div class="w-5 h-5 flex-shrink-0">
                <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
              </div>
            </div>
            <div class="flex items-center gap-1 mb-3">
              <div class="flex text-[15px]">
                <template v-for="i in 5" :key="i">
                  <span :class="i <= (testimonio.valoracion || 0) ? 'text-yellow-400' : 'text-gray-300'">★</span>
                </template>
              </div>
              <div v-if="testimonio.verificado" class="flex items-center justify-center w-4 h-4 rounded-full bg-blue-500">
                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
            </div>
            <div class="text-gray-700 text-[14px] leading-relaxed">
              <p v-if="testimonio.texto">{{ testimonio.texto }}</p>
              <p v-else class="italic text-gray-500 text-xs">Este usuario solo dejó una calificación.</p>
              <span v-if="testimonio.tieneMas" class="inline-block mt-2 text-gray-400 text-xs cursor-pointer hover:underline">
                Leer más
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

      <!-- Modal de autenticación (login/registro) -->
      <ModalAutenticacion v-model="showAuthModal" />

      <!-- Footer: enlaces informativos + contacto + redes -->
      <footer id="contacto-footer" class="bg-lanzarote-blue text-white mt-16" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div class="mb-12">
            <div class="flex items-center space-x-2 mb-4">
              <span class="text-3xl font-bold">LanzaTaxi</span>
            </div>
            <p class="text-white/80 max-w-2xl">
              La forma más inteligente de moverte por Lanzarote. Reserva tu taxi con seguimiento en tiempo real y llega a cualquier municipio de la isla de forma segura.
            </p>
          </div>

          <nav class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12" aria-label="Enlaces del sitio">
            <div>
              <h3 class="font-semibold text-lg mb-4">Empresa</h3>
              <ul class="space-y-3">
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Sobre nosotros</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Cómo funciona</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Opiniones</span></li>
              </ul>
            </div>
            <div>
              <h3 class="font-semibold text-lg mb-4">Productos</h3>
              <ul class="space-y-3">
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Pasajeros</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Taxistas</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Empresas</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Flotas</span></li>
              </ul>
            </div>
            <div>
              <h3 class="font-semibold text-lg mb-4">Soporte</h3>
              <ul class="space-y-3">
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Ayuda</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Seguridad</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Privacidad</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Términos</span></li>
              </ul>
            </div>
            <div>
              <h3 class="font-semibold text-lg mb-4">Legal</h3>
              <ul class="space-y-3">
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Aviso legal</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Política de cookies</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Protección de datos</span></li>
                <li><span class="text-white/70 hover:text-white transition-colors text-sm cursor-default">Condiciones generales</span></li>
              </ul>
            </div>
          </nav>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-8 border-t border-white/20">
            <div>
              <h3 class="font-semibold text-lg mb-4">Contacto</h3>
              <ul class="space-y-3">
                <li class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span class="text-white/80">{{ contactInfo.email }}</span>
                </li>
                <li class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span class="text-white/80">+34 928 123 456</span>
                </li>
                <li class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-white/80">Arrecife, Lanzarote</span>
                </li>
              </ul>
            </div>

            <div>
              <h3 class="font-semibold text-lg mb-4">Síguenos</h3>
              <div class="flex space-x-4" aria-label="Redes sociales">
                <span class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-lanzarote-yellow hover:text-black transition-colors cursor-default" aria-label="Facebook">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/>
                  </svg>
                </span>
                <span class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-lanzarote-yellow hover:text-black transition-colors cursor-default" aria-label="Twitter">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2.03 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.45-.38.1-.77.16-1.2.16-.3 0-.6-.03-.88-.08.6 1.9 2.35 3.28 4.42 3.32-1.62 1.27-3.66 2.03-5.88 2.03-.38 0-.76-.02-1.13-.07 2.1 1.34 4.6 2.13 7.3 2.13 8.75 0 13.53-7.25 13.53-13.53 0-.2 0-.4-.02-.6.93-.67 1.74-1.5 2.38-2.45z"/>
                  </svg>
                </span>
                <span class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-lanzarote-yellow hover:text-black transition-colors cursor-default" aria-label="Instagram">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.65-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.35 2.63 6.78 6.98 6.98 1.28.06 1.67.07 4.95.07s3.67-.01 4.95-.07c4.35-.2 6.78-2.63 6.98-6.98.06-1.28.07-1.67.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.63-6.78-6.98-6.98C15.67.01 15.26 0 12 0z"/>
                    <path d="M12 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zM12 16a4 4 0 110-8 4 4 0 010 8z"/>
                    <circle cx="18.41" cy="5.59" r="1.44"/>
                  </svg>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-black/20 py-4">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-sm text-white/60">
            <p>© {{ currentYear }} LanzaTaxi. Todos los derechos reservados.</p>
            <div class="flex space-x-6 mt-2 md:mt-0">
              <span class="hover:text-white transition-colors cursor-default">Privacidad</span>
              <span class="hover:text-white transition-colors cursor-default">Términos</span>
              <span class="hover:text-white transition-colors cursor-default">Cookies</span>
            </div>
          </div>
        </div>
      </footer>
    </main>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import BarraNavegacion from '../Componentes/BarraNavegacion.vue'
import ModalAutenticacion from '../Componentes/Autenticacion/ModalAutenticacion.vue'

const props = defineProps({
  opiniones: {
    type: Array,
    default: () => ([])
  }
})

// Control de visibilidad del modal de autenticación
const showAuthModal = ref(false)

// Contenido estático: pasos explicativos del proceso de reserva
const pasosReserva = [
  {
    numero: '1',
    titulo: 'Solicita tu taxi',
    descripcion: 'Indica dónde te encuentras y tu destino. Elige el tipo de vehículo que necesitas.'
  },
  {
    numero: '2',
    titulo: 'Sigue tu taxi en tiempo real',
    descripcion: 'Una vez asignado, podrás ver en el mapa dónde está tu taxi y el tiempo estimado de llegada.'
  },
  {
    numero: '3',
    titulo: 'Disfruta del viaje',
    descripcion: 'Sube al taxi y viaja tranquilo. Pago seguro sin efectivo si lo prefieres.'
  }
]

// Contenido estático: municipios y teléfonos de radio taxi
const municipios = [
  { 
    nombre: 'Arrecife', 
    desc: 'Capital y centro económico',
    telefonos: ['928 800 806', '928 803 104', '928 812 710'],
  },
  { 
    nombre: 'San Bartolomé', 
    desc: 'Corazón geográfico de la isla',
    telefonos: ['928 520 176'],
  },
  { 
    nombre: 'Teguise', 
    desc: 'Historia y tradición',
    telefonos: ['928 524 223'],
  },
  { 
    nombre: 'Tías', 
    desc: 'Turismo y playas',
    telefonos: ['928 524 220'],
  },
  { 
    nombre: 'Yaiza', 
    desc: 'Naturaleza volcánica',
    telefonos: ['928 524 222'],
  },
  { 
    nombre: 'Tinajo', 
    desc: 'Paisajes volcánicos',
    telefonos: ['928 840 049'],
  },
  { 
    nombre: 'Haría', 
    desc: 'Valle de las mil palmeras',
    telefonos: ['928 524 223', '620 315 350'],
  }
]

const testimoniosMock = [
  {
    nombre: 'Angie',
    valoracion: 5,
    texto: 'Agradezco mucho que me atendieran un sábado por la noche para asesorarme. Muy amables.',
    tieneMas: false,
    fecha: 'hace 2 días',
    verificado: true
  },
  {
    nombre: 'Carlos M.',
    valoracion: 5,
    texto: 'La app para ver por dónde viene el taxi funciona de lujo. Me salvó para llegar a tiempo a una reunión en Arrecife. Repetiré.',
    tieneMas: false,
    fecha: 'hace 1 semana',
    verificado: true
  },
  {
    nombre: 'María G.',
    valoracion: 4,
    texto: 'Muy buen servicio al aeropuerto. El conductor me ayudó con las maletas, que pesaban un quintal. Un pelín más caro que el bus pero merece la pena por la comodidad.',
    tieneMas: false,
    fecha: 'hace 3 días',
    verificado: true
  },
  {
    nombre: 'Roberto T.',
    valoracion: 5,
    texto: 'Puntuales. Es lo único que pido y siempre cumplen.',
    tieneMas: false,
    fecha: 'hace 5 días',
    verificado: true
  },
  {
    nombre: 'Laura S.',
    valoracion: 5,
    texto: 'Reservé por la web y el proceso es súper sencillo. Coche limpio y trato impecable por parte de David, el conductor.',
    tieneMas: false,
    fecha: 'hace 1 semana',
    verificado: true
  },
  {
    nombre: 'Javi Guerra',
    valoracion: 5,
    texto: 'Grandes profesionales, buen trabajo!!!',
    tieneMas: false,
    fecha: 'hace 2 semanas',
    verificado: true
  }
]

// Contenido de testimonios (si llegan opiniones reales del servidor, se usan)
const testimonios = ref((props.opiniones && props.opiniones.length > 0) ? props.opiniones : testimoniosMock)

// Utilidad: scroll suave a una sección concreta
const scrollToPasos = () => {
  const pasos = document.getElementById('como-funciona-section')
  if (pasos) {
    pasos.scrollIntoView({ behavior: 'smooth' })
  }
}

// Año actual para el copyright del footer
const currentYear = new Date().getFullYear()

// Estructura de enlaces del footer (por bloques)
const footerSections = {
  empresa: {
    title: 'Empresa',
    links: [
      { name: 'Sobre nosotros', url: '#' },
      { name: 'Cómo funciona', url: '#' },
      { name: 'Opiniones', url: '#', section: 'testimonios' }
    ]
  },
  productos: {
    title: 'Productos',
    links: [
      { name: 'Pasajeros', url: '#' },
      { name: 'Taxistas', url: '#' },
      { name: 'Empresas', url: '#' },
      { name: 'Flotas', url: '#' }
    ]
  },
  soporte: {
    title: 'Soporte',
    links: [
      { name: 'Ayuda', url: '#' },
      { name: 'Seguridad', url: '#' },
      { name: 'Privacidad', url: '#' },
      { name: 'Términos', url: '#' }
    ]
  },
  legal: {
    title: 'Legal',
    links: [
      { name: 'Aviso legal', url: '#' },
      { name: 'Política de cookies', url: '#' },
      { name: 'Protección de datos', url: '#' },
      { name: 'Condiciones generales', url: '#' }
    ]
  }
}

// Información de contacto (se muestra en el footer)
const contactInfo = {
  email: 'info@lanzataxi.es',
  phone: '+34 928 123 456',
  address: 'Arrecife, Lanzarote'
}

// Utilidad genérica: scroll suave a un id de sección
const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>
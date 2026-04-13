// Punto de entrada del frontend (Vite + Inertia + Vue)
// - Importa estilos globales
// - Configura Inertia (resolución de páginas)
// - Registra plugins (Ziggy, Pinia con persistencia)
import '../css/app.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';

import { crearI18n, resolverLocaleInicial } from './i18n';

// Nombre de la app (se usa para el title del documento)
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
	// Título dinámico por página
	title: (title) => (title ? `${title} - ${appName}` : appName),
	// Resolver componentes de páginas según nombre (convención Inertia)
	resolve: (name) =>
		resolvePageComponent(
			`./Paginas/${name}.vue`,
			import.meta.glob('./Paginas/**/*.vue')
		),
	setup({ el, App, props, plugin }) {
		// Pinia + persistencia (mantiene estado entre recargas si el store lo configura)
		const pinia = createPinia();
		pinia.use(piniaPluginPersistedstate);

		const pageProps = props?.initialPage?.props ?? {};
		const localeInicial = resolverLocaleInicial(pageProps);
		const i18n = crearI18n(localeInicial);

		// Montaje de la app Vue con los plugins necesarios
		createApp({ render: () => h(App, props) })
			.use(plugin)
			// Ziggy: helpers de rutas de Laravel en el frontend
			.use(ZiggyVue, window.Ziggy)
			.use(pinia)
			.use(i18n)
			.mount(el);
	},
	progress: {
		// Barra de progreso de Inertia durante navegación
		color: '#4B5563',
	},
});
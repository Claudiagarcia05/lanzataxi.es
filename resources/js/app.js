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

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
	// Define el título del documento combinando el título de página con el nombre de la app.
	title: (title) => (title ? `${title} - ${appName}` : appName),

	// Resuelve dinámicamente las páginas de Inertia desde `resources/js/Paginas/**`.
	// `import.meta.glob` permite code-splitting y carga bajo demanda.
	resolve: (name) =>
		resolvePageComponent(
			`./Paginas/${name}.vue`,
			import.meta.glob('./Paginas/**/*.vue')
		),
	setup({ el, App, props, plugin }) {
		// Pinia como store global; el plugin persistedstate mantiene partes del estado
		// (p.ej. sesión/usuario) entre recargas según lo que decida cada store.
		const pinia = createPinia();
		pinia.use(piniaPluginPersistedstate);

		// Locale inicial: normalmente viene de props del backend (Inertia) o se infiere.
		// Esto evita un "flash" de idioma al cargar por primera vez.
		const pageProps = props?.initialPage?.props ?? {};
		const localeInicial = resolverLocaleInicial(pageProps);
		const i18n = crearI18n(localeInicial);

		// Montaje de la app Inertia.
		// `plugin` es el adaptador de Inertia, Ziggy aporta helpers de rutas de Laravel.
		createApp({ render: () => h(App, props) })
			.use(plugin)
			.use(ZiggyVue, window.Ziggy)
			.use(pinia)
			.use(i18n)
			.mount(el);
	},
	progress: {
		// Color de la barra de progreso de Inertia durante navegación/carga.
		color: '#4B5563',
	},
});
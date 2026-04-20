<?php

    namespace App\Http\Middleware;

    use Carbon\Carbon;
    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\App;
    use Illuminate\Support\Str;

    /**
     * Establece el locale de la aplicación a partir de:
     * - Cookies (`locale` o `lanzataxi_locale`)
     * - Cabecera `Accept-Language`
     *
     * Además configura el locale de Carbon para formateo de fechas.
     */
    class EstablecerLocale {
        /**
         * Resuelve y aplica el locale. Si no está permitido, cae al default.
         */
        public function handle(Request $request, Closure $next) {
            $localesDisponibles = ['es', 'en'];

            // Preferencia: cookie explícita (útil para selector de idioma).
            $locale = $request->cookie('locale') ?? $request->cookie('lanzataxi_locale');

            if (!$locale) {
                // Fallback: primer idioma de Accept-Language (solo base: es/en).
                $accept = (string) $request->header('Accept-Language', '');
                $first = trim(Str::before($accept, ','));
                $base = Str::lower(Str::before($first, '-'));
                $locale = $base !== '' ? $base : null;
            }

            if (!is_string($locale) || !in_array($locale, $localesDisponibles, true)) {
                // Si llega cualquier valor inesperado, se usa el locale de config.
                $locale = config('app.locale', 'es');
            }

            App::setLocale($locale);
            Carbon::setLocale($locale);

            return $next($request);
        }
    }
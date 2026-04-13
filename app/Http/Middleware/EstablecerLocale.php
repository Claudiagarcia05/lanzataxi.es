<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class EstablecerLocale {
    public function handle(Request $request, Closure $next) {
        $localesDisponibles = ['es', 'en'];

        $locale = $request->cookie('locale');

        if (!$locale) {
            $accept = (string) $request->header('Accept-Language', '');
            $first = trim(Str::before($accept, ','));
            $base = Str::lower(Str::before($first, '-'));
            $locale = $base !== '' ? $base : null;
        }

        if (!is_string($locale) || !in_array($locale, $localesDisponibles, true)) {
            $locale = config('app.locale', 'es');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}

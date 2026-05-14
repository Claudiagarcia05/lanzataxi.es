<?php

    /*
    |--------------------------------------------------------------------------
    | Web Routes (Inertia)
    |--------------------------------------------------------------------------
    | Estas rutas sirven páginas Inertia/Vue (SPA) y algunas utilidades web.
    | Importante: aquí se decide qué vistas se renderizan según rol, y se
    | aplican middlewares para proteger paneles y evitar acceso por URL.
    */

    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\AuthSessionController;
    use App\Models\User;
    use App\Models\Viaje;
    use Illuminate\Foundation\Application;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Route;
    use Inertia\Inertia;

    // Helper para la landing: obtiene opiniones verificadas a partir de viajes completados.
    // Se anonimiza el nombre (nombre + inicial) para privacidad.
    $obtenerOpinionesLanding = function () {
        try {
            return Viaje::query()
                ->where('status', 'completed')
                ->whereNotNull('rating')
                ->with(['pasajero:id,name'])
                ->orderByDesc('end_time')
                ->limit(6)
                ->get()
                ->map(function (Viaje $viaje) {
                    $nombreOriginal = (string) ($viaje->pasajero?->name ?? 'Usuario');
                    $partes = preg_split('/\s+/', trim($nombreOriginal)) ?: [];
                    $nombre = $partes[0] ?? 'Usuario';
                    if (isset($partes[1]) && $partes[1] !== '') {
                        $nombre .= ' ' . mb_substr($partes[1], 0, 1) . '.';
                    }

                    return [
                        'nombre' => $nombre,
                        'valoracion' => (int) $viaje->rating,
                        'texto' => $viaje->comment,
                        'tieneMas' => false,
                        'fecha' => $viaje->end_time?->diffForHumans() ?? null,
                        'verificado' => true,
                    ];
                })
                ->values();
        } catch (\Throwable $e) {
            // No rompemos la home por un fallo puntual de BD/relaciones.
            report($e);

            return collect();
        }
    };

    // Resumen ambiental para la landing.
    // Se usa para mostrar métricas globales de plataforma y alimentar la calculadora eco.
    $obtenerResumenSostenibilidad = function () {
        try {
            $conteoRoles = User::query()
                ->select('role', DB::raw('COUNT(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role');

            $baseViajesCompletados = Viaje::query()->where('status', 'completed');

            $viajesCompletados = (clone $baseViajesCompletados)->count();
            $distanciaTotalKm = (float) ((clone $baseViajesCompletados)->sum('distance') ?? 0);
            $co2TotalKg = (float) ((clone $baseViajesCompletados)->sum('co2_saved') ?? 0);

            // Estimación sencilla de ahorro por digitalización (evitar papeleo por gestión).
            // Supuesto: 2 hojas A4 por viaje evitadas, 5g/hoja, 1.3 kg CO2e por kg de papel.
            $kgCo2PorHojaA4 = 0.0065;
            $hojasEvitadasPorViaje = 2;
            $co2PapelEvitadoKg = $viajesCompletados * $hojasEvitadasPorViaje * $kgCo2PorHojaA4;

            return [
                'usersByRole' => [
                    'passenger' => (int) ($conteoRoles['pasajero'] ?? 0),
                    'driver' => (int) ($conteoRoles['conductor'] ?? 0),
                    'admin' => (int) ($conteoRoles['admin'] ?? 0),
                ],
                'completedTrips' => (int) $viajesCompletados,
                'distanceKm' => round($distanciaTotalKm, 2),
                'co2SavedKg' => round($co2TotalKg, 2),
                'paperCo2SavedKg' => round($co2PapelEvitadoKg, 2),
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'usersByRole' => [
                    'passenger' => 0,
                    'driver' => 0,
                    'admin' => 0,
                ],
                'completedTrips' => 0,
                'distanceKm' => 0,
                'co2SavedKg' => 0,
                'paperCo2SavedKg' => 0,
            ];
        }
    };

    // Endpoint para cambiar el idioma guardándolo en cookies.
    // Se escribe doble cookie (host y dominio base) para cubrir entornos con subdominios.
    // También se conserva una cookie legacy por compatibilidad.
    Route::post('/locale', function (\Illuminate\Http\Request $request) {
        $locale = (string) $request->input('locale', '');
        if (!in_array($locale, ['es', 'en'], true)) {
            return response()->json([
                'message' => 'Locale inválido',
            ], 422);
        }

        $host = (string) $request->getHost();
        $dominioBase = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        $dominio = config('session.domain') ?: $dominioBase;
        $segura = config('session.secure');
        $sameSite = strtolower((string) config('session.same_site', 'lax'));

        // Si SameSite=None, el estándar exige Secure=true.
        if ($sameSite === 'none') {
            $segura = true;
        }

        $cookieLocaleHost = cookie(
            name: 'locale',
            value: $locale,
            minutes: 60 * 24 * 365,
            path: '/',
            domain: null,
            secure: $segura,
            httpOnly: false,
            raw: false,
            sameSite: $sameSite
        );

        $cookieLocaleDomain = cookie(
            name: 'locale',
            value: $locale,
            minutes: 60 * 24 * 365,
            path: '/',
            domain: $dominio,
            secure: $segura,
            httpOnly: false,
            raw: false,
            sameSite: $sameSite
        );

        $cookieLegacyHost = cookie(
            name: 'lanzataxi_locale',
            value: $locale,
            minutes: 60 * 24 * 365,
            path: '/',
            domain: null,
            secure: $segura,
            httpOnly: false,
            raw: false,
            sameSite: $sameSite
        );

        $cookieLegacyDomain = cookie(
            name: 'lanzataxi_locale',
            value: $locale,
            minutes: 60 * 24 * 365,
            path: '/',
            domain: $dominio,
            secure: $segura,
            httpOnly: false,
            raw: false,
            sameSite: $sameSite
        );

        return response()
            ->noContent()
            ->withCookie($cookieLocaleHost)
            ->withCookie($cookieLocaleDomain)
            ->withCookie($cookieLegacyHost)
            ->withCookie($cookieLegacyDomain);
    })->name('locale.set');

    // Páginas públicas de soporte y legal.
    Route::get('/aviso-legal', function () {
        return Inertia::render('Legal/AvisoLegal');
    })->name('legal.notice');

    Route::get('/politica-cookies', function () {
        return Inertia::render('Legal/PoliticaCookies');
    })->name('legal.cookies');

    Route::get('/proteccion-datos', function () {
        return Inertia::render('Legal/ProteccionDatos');
    })->name('legal.privacy');

    Route::get('/productos', function () {
        return Inertia::render('Publico/Productos');
    })->name('public.products');

    Route::get('/soporte', function () {
        return Inertia::render('Publico/Soporte');
    })->name('public.support');

    Route::get('/empresa', function () {
        return Inertia::render('Publico/Empresa');
    })->name('public.company');

    // Landing pública.
    Route::get('/', function () use ($obtenerOpinionesLanding, $obtenerResumenSostenibilidad) {

        $opiniones = $obtenerOpinionesLanding();
        $sostenibilidad = $obtenerResumenSostenibilidad();

        return Inertia::render('Inicio', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'opiniones' => $opiniones,
            'sostenibilidad' => $sostenibilidad,
        ]);
    });

    // Ruta interna/experimental para prototipos.
    Route::get('/prototype', function () {

        return Inertia::render('Prototype');
    })->name('prototype');

    // Puente token -> sesión web (útil para que un token API "inicie" sesión web).
    Route::get('/auth/session-login', [AuthSessionController::class, 'establishSession'])
        ->name('auth.session-login');

    // Áreas privadas: requiere sesión y cuenta habilitada.
    Route::middleware(['auth', 'account.enabled'])->group(function () {
        Route::get('/dashboard', function () {
            // Redirección al dashboard correcto según rol.
            return match (auth()->user()->role) {
                'conductor' => redirect()->route('conductor.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                default => redirect()->route('pasajero.dashboard'),
            };
        })->name('dashboard');

        // Panel y secciones de pasajero.
        Route::middleware('role:pasajero')->group(function () {
            Route::get('/pasajero/home', function () {

                return Inertia::render('Pasajero/Panel');
            })->name('pasajero.dashboard');

            Route::get('/pasajero/reservas', function () {

                return Inertia::render('Pasajero/Reservas');
            })->name('pasajero.reservas');

            Route::get('/pasajero/seguimiento/{viaje}', function (Viaje $viaje) {
                // Seguridad: un pasajero solo puede ver el seguimiento de SUS viajes.
                abort_unless((int) $viaje->pasajero_id === (int) auth()->id(), 403);

                return Inertia::render('Pasajero/Seguimiento', [
                    'viajeId' => $viaje->id,
                ]);
            })->name('pasajero.seguimiento');

            Route::get('/pasajero/perfil', function () {

                return Inertia::render('Pasajero/Perfil');
            })->name('pasajero.perfil');

            Route::get('/dashboard/cartera', function () {

                return Inertia::render('Pasajero/Cartera');
            })->name('pasajero.wallet');

            Route::get('/dashboard/viajes', function () {

                return Inertia::render('Pasajero/Panel');
            })->name('pasajero.viajes');
        });

        // Panel y secciones de conductor (además, conductor debe estar aprobado).
        Route::middleware(['role:conductor', 'conductor.approved'])->group(function () {
            Route::get('/conductor/dashboard', function () {

                return Inertia::render('Conductor/Panel');
            })->name('conductor.dashboard');

            Route::get('/conductor/viajes', function () {

                return Inertia::render('Conductor/Panel');
            })->name('conductor.viajes');

            Route::get('/conductor/ganancias', function () {

                return Inertia::render('Conductor/Ganancias');
            })->name('conductor.earnings');

            Route::get('/conductor/perfil', function () {

                return Inertia::render('Conductor/Perfil');
            })->name('conductor.perfil');
        });

        // Panel y secciones de administrador.
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/dashboard', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.dashboard');

            Route::get('/administradir/home', function () {
                // Alias/histórico: mantiene compatibilidad con una URL anterior.
                return redirect()->route('admin.dashboard');
            });

            Route::get('/admin/viajes', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.viajes');

            Route::get('/admin/users', function () {
                // Redirección para mantener URLs antiguas.
                return redirect('/admin/usuarios');
            })->name('admin.users');

            Route::get('/admin/usuarios', function () {
                // Redirección: en UI se muestra el listado bajo taxistas.
                return redirect('/admin/taxistas');
            })->name('admin.usuarios');

            Route::get('/admin/taxistas', function () {

                return Inertia::render('Administrador/Taxistas');
            })->name('admin.taxistas');

            Route::get('/admin/clientes', function () {

                return Inertia::render('Administrador/Clientes');
            })->name('admin.clientes');

            Route::get('/admin/admins', function () {

                return Inertia::render('Administrador/Admin');
            })->name('admin.admins');

            Route::get('/admin/taxis', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.taxis');

            Route::get('/admin/reports', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.reports');

            Route::get('/perfil', function () {

                return Inertia::render('Administrador/Perfil');
            })->name('admin.perfil');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';

    // Catch-all para la SPA: cualquier ruta no declarada renderiza la landing.
    // Útil para URLs compartidas/refresh del navegador, evitando 404 del servidor.
    Route::get('/{any}', function () use ($obtenerOpinionesLanding) {

        $opiniones = $obtenerOpinionesLanding();

        return Inertia::render('Inicio', [
            'opiniones' => $opiniones,
        ]);
    })->where('any', '.*');
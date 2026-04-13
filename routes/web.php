<?php

    // Rutas web (Inertia):
    // - Página de inicio
    // - Rutas de prototipo
    // - Acceso por roles (pasajero / conductor / admin)
    // - Rutas de perfil (Breeze/Jetstream style)

    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\AuthSessionController;
    use App\Models\Viaje;
    use Illuminate\Foundation\Application;
    use Illuminate\Support\Facades\Route;
    use Inertia\Inertia;

    $obtenerOpinionesLanding = function () {
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
    };

    // Landing / Inicio
    Route::get('/', function () {

        $opiniones = $obtenerOpinionesLanding();

        return Inertia::render('Inicio', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'opiniones' => $opiniones,
        ]);
    });

    // Vista de prototipado (para pruebas internas)
    Route::get('/prototype', function () {

        return Inertia::render('Prototype');
    })->name('prototype');

    // Establece sesión web a partir de un token (flujo híbrido API -> sesión)
    Route::get('/auth/session-login', [AuthSessionController::class, 'establishSession'])
        ->name('auth.session-login');

    // Rutas protegidas por autenticación (sesión Laravel)
    Route::middleware(['auth', 'account.enabled'])->group(function () {
        // Dashboard genérico: redirige según el rol del usuario
        Route::get('/dashboard', function () {

            return match (auth()->user()->role) {
                'conductor' => redirect()->route('conductor.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                default => redirect()->route('pasajero.dashboard'),
            };
        })->name('dashboard');

        // Rutas del PASAJERO
        Route::middleware('role:pasajero')->group(function () {
            // Panel principal del pasajero
            Route::get('/pasajero/home', function () {

                return Inertia::render('Pasajero/Panel');
            })->name('pasajero.dashboard');

            // Historial y reservas del pasajero
            Route::get('/pasajero/reservas', function () {

                return Inertia::render('Pasajero/Reservas');
            })->name('pasajero.reservas');

            // Seguimiento del viaje (solo si el viaje pertenece al pasajero autenticado)
            Route::get('/pasajero/seguimiento/{viaje}', function (Viaje $viaje) {
                abort_unless((int) $viaje->pasajero_id === (int) auth()->id(), 403);

                return Inertia::render('Pasajero/Seguimiento', [
                    'viajeId' => $viaje->id,
                ]);
            })->name('pasajero.seguimiento');

            // Perfil del pasajero
            Route::get('/pasajero/perfil', function () {

                return Inertia::render('Pasajero/Perfil');
            })->name('pasajero.perfil');

            // Cartera del pasajero
            Route::get('/dashboard/cartera', function () {

                return Inertia::render('Pasajero/Cartera');
            })->name('pasajero.wallet');

            // Alias/entrada adicional para viajes del pasajero (renderiza panel)
            Route::get('/dashboard/viajes', function () {

                return Inertia::render('Pasajero/Panel');
            })->name('pasajero.viajes');
        });

        // Rutas del CONDUCTOR
        Route::middleware(['role:conductor', 'conductor.approved'])->group(function () {
            // Panel principal del conductor
            Route::get('/conductor/dashboard', function () {

                return Inertia::render('Conductor/Panel');
            })->name('conductor.dashboard');

            // Alias/entrada adicional a viajes del conductor (renderiza panel)
            Route::get('/conductor/viajes', function () {

                return Inertia::render('Conductor/Panel');
            })->name('conductor.viajes');

            // Pantalla de ganancias del conductor
            Route::get('/conductor/ganancias', function () {

                return Inertia::render('Conductor/Ganancias');
            })->name('conductor.earnings');

            // Perfil del conductor
            Route::get('/conductor/perfil', function () {

                return Inertia::render('Conductor/Perfil');
            })->name('conductor.perfil');
        });

        // Rutas del ADMIN
        Route::middleware('role:admin')->group(function () {
            // Home del panel de administración (URL canónica)
            Route::get('/admin/dashboard', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.dashboard');

            // Compatibilidad: URL anterior con typo
            Route::get('/administradir/home', function () {

                return redirect()->route('admin.dashboard');
            });

            // Secciones del admin (actualmente renderizan la misma vista)
            Route::get('/admin/viajes', function () {

                return Inertia::render('Administrador/Panel');
            })->name('admin.viajes');

            Route::get('/admin/users', function () {

                return redirect('/admin/usuarios');
            })->name('admin.users');

            Route::get('/admin/usuarios', function () {

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

            // Perfil del administrador
            Route::get('/perfil', function () {

                return Inertia::render('Administrador/Perfil');
            })->name('admin.perfil');
        });

        // Perfil (scaffolding Laravel)
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Rutas de autenticación de Laravel (Breeze)
    require __DIR__.'/auth.php';

    // Catch-all: cualquier ruta no definida renderiza Inicio
    // Útil para SPA con Inertia cuando el frontend maneja enlaces.
    Route::get('/{any}', function () {

        $opiniones = $obtenerOpinionesLanding();

        return Inertia::render('Inicio', [
            'opiniones' => $opiniones,
        ]);
    })->where('any', '.*');
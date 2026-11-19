<?php

use App\Enums\Rol;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\DocenteHuellaController;
use App\Http\Controllers\DocentePanelController;
use App\Http\Controllers\LectorController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/** Arma "EnsureRole:rol1,rol2" sin depender de un alias registrado en bootstrap/app.php. */
$rol = fn (Rol ...$roles) => EnsureRole::class . ':' . implode(',', array_map(fn (Rol $r) => $r->value, $roles));

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth')->group(function () use ($rol) {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/home', fn () => auth()->user()->esDocente()
        ? redirect()->route('docente.panel')
        : view('home'))->name('home');

    // Estado del lector y de las órdenes enviadas (cada persona solo ve las suyas).
    Route::get('/lector/en-linea', [LectorController::class, 'enLinea'])->name('lector.en-linea');
    Route::get('/lector/comandos/{comando}', [LectorController::class, 'estado'])->name('lector.estado');
    Route::delete('/lector/comandos/{comando}', [LectorController::class, 'cancelar'])->name('lector.cancelar');

    // ── Docente (se usa desde la Raspberry) ─────────────────────────────────────
    Route::middleware($rol(Rol::Docente))->group(function () {
        Route::get('/docente', [DocentePanelController::class, 'index'])->name('docente.panel');
        Route::post('/docente/asistencia', [DocentePanelController::class, 'iniciar'])->name('docente.asistencia.iniciar');
    });

    // ── Consulta y corrección de asistencias ───────────────────────────────────
    Route::middleware($rol(Rol::Secretario, Rol::JefePreceptores))->group(function () {
        Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
        Route::get('/asistencias/{asistencia}/editar', [AsistenciaController::class, 'edit'])->name('asistencias.edit');
        Route::put('/asistencias/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencias.update');
    });

    // ── Gestión (solo secretario) ──────────────────────────────────────────────
    Route::middleware($rol(Rol::Secretario))->group(function () {
        Route::resource('docentes', DocenteController::class)->except(['show']);
        Route::patch('/docentes/{docente}/activar', [DocenteController::class, 'activar'])->name('docentes.activar');
        Route::patch('/docentes/{docente}/desactivar', [DocenteController::class, 'desactivar'])->name('docentes.desactivar');

        Route::post('/docentes/{docente}/huellas/enrolar', [DocenteHuellaController::class, 'enrolar'])->name('docentes.huellas.enrolar');
        Route::delete('/docentes/{docente}/huellas/{huella}', [DocenteHuellaController::class, 'destroy'])
            ->scopeBindings()
            ->name('docentes.huellas.destroy');

        Route::get('/cuentas', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/cuentas', [UsuarioController::class, 'store'])->name('usuarios.store');

        Route::get('/lector', [LectorController::class, 'diagnostico'])->name('lector.diagnostico');
    });
});

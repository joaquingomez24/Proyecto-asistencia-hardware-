<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('home');
    })->name('dashboard');

    Route::get('/home', function () {
        return view('home');
    })->name('home');

    // Dashboards por rol
    Route::get('/prosecretario/dashboard', [AsistenciaController::class, 'index'])->name('prosecretario.index');
    Route::get('/jefe/dashboard', [AsistenciaController::class, 'index'])->name('jefe.index');

    // Gestión de Asistencias
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::get('/asistencia/registrar', [AsistenciaController::class, 'create'])->name('asistencia.registrar');
    Route::post('/asistencia', [AsistenciaController::class, 'store'])->name('asistencia.marcar');
    Route::get('/asistencia/editar', [AsistenciaController::class, 'edit'])->name('asistencia.edit');
    Route::put('/asistencia/actualizar', [AsistenciaController::class, 'update'])->name('asistencia.update');

    // Gestión de Docentes
    Route::get('/docentes/create', [DocenteController::class, 'create'])->name('docentes.create');
    Route::post('/docentes', [DocenteController::class, 'store'])->name('docentes.store');
    Route::get('/docentes/buscar', [DocenteController::class, 'buscar'])->name('docentes.buscar');
    Route::get('/docentes/{id}/editar', [DocenteController::class, 'edit'])->name('docentes.edit');
    Route::put('/docentes/{id}', [DocenteController::class, 'update'])->name('docentes.update');
    
    // RUTAS PARA DESACTIVAR Y BORRAR DOCENTE
    Route::patch('/docentes/{id}/desactivar', [DocenteController::class, 'desactivar'])->name('docentes.desactivar');
    Route::patch('/docentes/{id}/activar', [DocenteController::class, 'activar'])->name('docentes.activar');
    Route::delete('/docentes/{id}', [DocenteController::class, 'destroy'])->name('docentes.destroy');

    // Gestión de Horarios
    Route::delete('/horarios/{id}', [DocenteController::class, 'destroyMateria'])->name('horarios.destroy');

    // Gestión de Usuarios
    Route::get('/crear-cuenta', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/crear-cuenta', [UsuarioController::class, 'store'])->name('usuarios.store');
});
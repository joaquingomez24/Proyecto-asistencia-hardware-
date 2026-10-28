<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\DocenteDashboardController;
use App\Http\Controllers\DocenteHuellaController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::view('/dy50/pruebas', 'dy50.pruebas')->name('dy50.pruebas');

// Reemplazo del logout por el controlador seguro
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('home');
    })->name('dashboard');

    Route::get('/home', function () {
        return view('home');
    })->name('home');

    // Dashboards y Listados protegidos por rol
    Route::get('/prosecretario/dashboard', [AsistenciaController::class, 'index'])
        ->middleware('role:prosecretario')
        ->name('prosecretario.index');

    Route::get('/jefe/dashboard', [AsistenciaController::class, 'index'])
        ->middleware('role:jefe_preceptores')
        ->name('jefe.index');

    // Dashboard del Docente
    Route::get('/docente/dashboard', [DocenteDashboardController::class, 'index'])
        ->middleware('role:docente')
        ->name('docente.dashboard');

    // Gestión de Asistencias
    Route::get('/asistencia', [AsistenciaController::class, 'index'])
        ->middleware('role:prosecretario,jefe_preceptores')
        ->name('asistencia.index');
        
    Route::get('/asistencia/registrar', [AsistenciaController::class, 'create'])->name('asistencia.registrar');
    
    Route::post('/asistencia', [AsistenciaController::class, 'store'])
        ->middleware('role:docente')
        ->name('docente.asistencia.store'); // Mapeado correctamente al store del docente
        
    Route::get('/asistencia/editar', [AsistenciaController::class, 'edit'])
        ->middleware('role:jefe_preceptores')
        ->name('asistencia.edit');
        
    Route::put('/asistencia/actualizar', [AsistenciaController::class, 'update'])
        ->middleware('role:jefe_preceptores')
        ->name('asistencia.update');

    // Gestión de Docentes y Huellas (Restringido a Prosecretario)
    Route::get('/docentes/create', [DocenteController::class, 'create'])->middleware('role:prosecretario')->name('docentes.create');
    Route::post('/docentes', [DocenteController::class, 'store'])->middleware('role:prosecretario')->name('docentes.store');
    Route::get('/docentes/buscar', [DocenteController::class, 'buscar'])->name('docentes.buscar');
    Route::get('/docentes/{id}/editar', [DocenteController::class, 'edit'])->middleware('role:prosecretario')->name('docentes.edit');
    Route::put('/docentes/{id}', [DocenteController::class, 'update'])->middleware('role:prosecretario')->name('docentes.update');
    
    Route::patch('/docentes/{id}/desactivar', [DocenteController::class, 'desactivar'])->middleware('role:prosecretario')->name('docentes.desactivar');
    Route::patch('/docentes/{id}/activar', [DocenteController::class, 'activar'])->middleware('role:prosecretario')->name('docentes.activar');
    Route::delete('/docentes/{id}', [DocenteController::class, 'destroy'])->middleware('role:prosecretario')->name('docentes.destroy');

    // Vinculación de Huellas Físicas (DY50) con Docentes
    Route::post('/docentes/{docente}/huellas', [DocenteHuellaController::class, 'store'])
        ->middleware('role:prosecretario')
        ->name('docentes.huellas.store');

    // Gestión de Horarios
    Route::delete('/horarios/{id}', [DocenteController::class, 'destroyMateria'])->name('horarios.destroy');

    // Gestión de Usuarios (Restringido a Prosecretario)
    Route::get('/crear-cuenta', [UsuarioController::class, 'index'])->middleware('role:prosecretario')->name('usuarios.index');
    Route::post('/crear-cuenta/store', [UsuarioController::class, 'store'])->middleware('role:prosecretario')->name('usuarios.store');
});
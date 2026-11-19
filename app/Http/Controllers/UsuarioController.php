<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Cuentas de acceso que administra el secretario. */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            'usuarios' => User::query()->with('docente')->orderBy('usuario')->get(),
            'docentesSinCuenta' => Docente::query()->whereDoesntHave('usuario')->orderBy('apellido')->orderBy('nombre')->get(),
            'roles' => [Rol::Secretario, Rol::JefePreceptores, Rol::Docente],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'rol' => ['required', Rule::in([
                Rol::Secretario->value,
                Rol::JefePreceptores->value,
                Rol::Docente->value,
            ])],
            'usuario' => ['required', 'string', 'max:100', Rule::unique('users', 'usuario')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'docente_id' => ['nullable', 'integer', 'exists:docentes,id', Rule::unique('users', 'docente_id')],
            'dni' => ['nullable', 'digits_between:7,10'],
        ], [
            'docente_id.unique' => 'Ese docente ya tiene una cuenta de usuario.',
        ]);

        if ($datos['rol'] === Rol::Docente->value) {
            if (empty($datos['docente_id'])) {
                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['docente_id' => 'Para crear una cuenta docente, elegí primero la ficha del docente.']);
            }

            // El DNI sale de la ficha, así no pueden quedar distintos.
            $datos['dni'] = Docente::findOrFail($datos['docente_id'])->dni;
        } else {
            $datos['docente_id'] = null;

            if (empty($datos['dni'])) {
                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['dni' => 'El DNI es obligatorio.']);
            }
        }

        if (User::where('dni', $datos['dni'])->exists()) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['dni' => 'Ya existe una cuenta con ese DNI.']);
        }

        User::create($datos);

        return back()->with('exito', 'Cuenta creada correctamente.');
    }
}

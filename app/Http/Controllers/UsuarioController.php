<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->rol === 'prosecretario', 403);

        $usuarios = User::query()->orderBy('usuario')->get(['id', 'usuario', 'dni', 'email', 'rol', 'id_docente']);
        $docentesSinCuenta = Docente::query()
            ->whereDoesntHave('usuario')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get(['id_docente', 'DNI', 'nombre', 'apellido']);

        return view('usuarios.create', compact('usuarios', 'docentesSinCuenta'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->rol === 'prosecretario', 403);

        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:100', Rule::unique('users', 'usuario')],
            'dni' => ['required', 'digits_between:7,10', Rule::unique('users', 'dni')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(['docente', 'prosecretario', 'jefe_preceptores'])],
            'id_docente' => ['nullable', 'integer', 'exists:docentes,id_docente'],
        ]);

        if ($datos['rol'] === 'docente' && empty($datos['id_docente'])) {
            return back()->withErrors([
                'id_docente' => 'Para crear una cuenta docente, primero elegí el registro del docente correspondiente.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        if ($datos['rol'] !== 'docente') {
            $datos['id_docente'] = null;
        } elseif (User::where('id_docente', $datos['id_docente'])->exists()) {
            return back()->withErrors([
                'id_docente' => 'Ese docente ya tiene una cuenta de usuario.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        DB::transaction(function () use ($datos) {
            User::create([
                'usuario' => $datos['usuario'],
                'dni' => $datos['dni'],
                'email' => $datos['email'],
                'password' => $datos['password'],
                'rol' => $datos['rol'],
                'id_docente' => $datos['id_docente'] ?? null,
            ]);
        });

        return back()->with('exito', 'Cuenta creada correctamente.');
    }
}

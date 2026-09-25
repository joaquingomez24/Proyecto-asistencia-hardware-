<?php

namespace App\Http\Controllers;

use App\Models\Prosecretario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index()
    {
        // probando
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }//fin
       $usuarios = DB::table('users')
        ->select('rol', 'name as usuario')
        ->get();

    return view('usuarios.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        // probando
        if (auth()->user()->rol !== 'prosecretario') {
            return redirect()->route('home');
        }//fin
        // Elegir la tabla destino según el rol para validar duplicados
        $tabla = $request->rol === 'prosecretario' ? 'prosecretario' : 'jefe_preceptors';

        $request->validate([
            'rol' => 'required|in:prosecretario,jefe_preceptores',
            'usuario' => "required|string|max:50|unique:{$tabla},usuario",
            'password' => 'required|string|min:6',
            'dni' => "required|numeric|digits_between:7,8|unique:{$tabla},dni",
            'email' => "required|email|unique:{$tabla},email",
        ], [
            'rol.required' => 'El rol es obligatorio.',
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.unique' => 'Este nombre de usuario ya existe para este rol.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.numeric' => 'El DNI debe contener solo números.',
            'dni.unique' => 'Este DNI ya está registrado.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Ingrese un email válido.',
            'email.unique' => 'Este email ya está registrado.',
        ]);

        $datos = [
            'usuario' => $request->usuario,
            'contraseña' => Hash::make($request->password),
            'dni' => $request->dni,
            'email' => $request->email,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table($tabla)->insert($datos);

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado con éxito.');
    }
}
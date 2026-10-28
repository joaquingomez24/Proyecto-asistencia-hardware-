<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('usuario', $datos['usuario'])
            ->where('dni', $datos['dni'])
            ->first();

        if (!$user || !Hash::check($datos['password'], $user->password)) {
            return back()
                ->withErrors(['error' => 'Usuario, DNI o contraseña incorrectos.'])
                ->withInput($request->only('usuario', 'dni'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return match ($user->rol) {
            'docente' => redirect()->route('docente.dashboard'),
            'prosecretario' => redirect()->route('prosecretario.index'),
            'jefe_preceptores' => redirect()->route('jefe.index'),
            default => $this->cerrarSesionPorRolDesconocido($request),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function cerrarSesionPorRolDesconocido(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['error' => 'La cuenta no tiene un rol válido. Contactá a Secretaría.']);
    }
}

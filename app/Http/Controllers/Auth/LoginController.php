<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

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

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            return back()
                ->withErrors(['error' => 'Usuario, DNI o contraseña incorrectos.'])
                ->withInput($request->only('usuario', 'dni'));
        }

        if ($user->esDocente() && ! $user->docente?->activo) {
            return back()
                ->withErrors(['error' => 'Tu cuenta no está vinculada a un docente activo. Consultá a Secretaría.'])
                ->withInput($request->only('usuario', 'dni'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $user->esDocente()
            ? redirect()->route('docente.panel')
            : redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

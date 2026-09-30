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
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario'  => 'required|string',
            'password' => 'required|string',
            'dni'      => 'required|string',
        ]);

        // Buscar al usuario que coincida con el usuario Y el DNI
        $user = User::where('usuario', $request->usuario)
                    ->where('dni', $request->dni)
                    ->first();

        // Verificar si el usuario existe y la contraseña coincide
        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'error' => 'Usuario, contraseña o DNI incorrectos.',
        ])->withInput($request->only('usuario', 'dni'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/home');
    }
}
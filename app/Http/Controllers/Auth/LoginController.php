<?php

Namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prosecretario;
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
        'usuario' => 'required',
        'password' => 'required',
    ]);

    // Buscar al usuario en la tabla de usuarios
    $user = \App\Models\User::where('usuario', $request->usuario)->first();

    if ($user && Hash::check($request->password, $user->password)) {
        Auth::login($user);
        $request->session()->regenerate();

       // Redirección al Inicio para todos
    return redirect('/home');
    }

    return back()->withErrors([
        'error' => 'Usuario o contraseña incorrectos.',
    ])->withInput($request->only('usuario'));
}
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/home');
    }

   
}
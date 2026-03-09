<?php


namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showRegister()
    {
        return redirect()->route('login')
        ->withErrors(['email' => 'Não é possível fazer registro. Contate o administrador.']);
    }

    public function register(Request $request)
    {
        return redirect()->route('login')
        ->withErrors(['email' => 'Não é possível fazer registro. Contate o administrador.'
    ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Conta criada com sucesso.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            if (Auth::user()->role !== 'admin'){
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

        return back()->withErrors(['email' => 'Acesso permitido somente para administradores.'])->withInput();
    }
    return redirect()->route('dashboard');
            }
    return back()->withErrors(['email' => 'Email ou senha incorretos.'])->withInput();
            
    }
    

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');

    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|email|max:250|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        //admin principal, primeiro usuário sem dono.
        $admin = User::whereNull('owner_id')->orderBy('id')->first();
        $isFirstUser = is_null($admin);

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),

            //1º usuário do sistema vira o admin
            'owner_id'    => $isFirstUser ? null : $admin->id,
            'status'      => $isFirstUser ? 'active' : 'pending',
            'permissions' => $isFirstUser ? null : [],
        ]);

        //caso especial: o primeiro usuário do sistema é o admin, entra direto.
        if ($isFirstUser) {
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Conta criada com sucesso.');
        }

        //avisa o admin e manda o usuário pra tela de espera.
        $this->notifyAdminNewSignup($user, $admin);

        return redirect()->route('register.pending');
    }

    public function showPending()
    {
        return view('auth.pending');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            //tela dizendo que ainda não foi aprovado.
            if ($user->isPending()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('register.pending');
            }
            if ($user->isRejected()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Seu acesso foi recusado pelo administrador.'])->withInput();
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email ou senha incorretos.'])
            ->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    //Notifica o admin
    private function notifyAdminNewSignup(User $user, ?User $admin): void
    {
        if (!$admin) {
            return;
        }
        $desc = 'Novo cadastro: ' . $user->name . ' (' . $user->email . ') quer entrar na equipe.';

        try {
            ActivityLog::create([
                'owner_id'    => $admin->id,
                'actor_id'    => $user->id,
                'action'      => 'solicitou acesso',
                'subject'     => 'novo cadastro',
                'description' => $desc,
            ]);

            if (!empty($admin->email)) {
                Mail::raw(
                    $desc . "\n\nAprove ou rejeite no seu Perfil, na seção \"Novos cadastros\".",
                    function ($m) use ($admin) {
                        $m->to($admin->email)->subject('Nexo Estoque — Novo cadastro aguardando aprovação');
                    }
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

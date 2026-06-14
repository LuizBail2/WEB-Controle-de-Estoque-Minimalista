<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    //"Esqueci a senha" pede o e-mail
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    //envia o link de redefinição para o e-mail
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Enviamos um link de redefinição para o seu e-mail. Confira a caixa de entrada (e o spam).');
        }

        return back()
            ->withErrors(['email' => 'Não encontramos uma conta com esse e-mail.'])
            ->withInput();
    }

    //definir a nova senha, vinda do link do e-mail
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    //Salva a nova senha
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Senha redefinida! Faça login com a nova senha.');
        }

        return back()
            ->withErrors(['email' => 'Não foi possível redefinir. O link pode ter expirado — peça um novo.'])
            ->withInput();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    //tela confirme seu e-mail
    public function notice(Request $request)
    {
        return view('auth.verify-notice', [
            'email' => session('verify_email') ?? optional($request->user())->email,
        ]);
    }

    //Link clicado no e-mail
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Link de confirmação inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'E-mail já confirmado. Faça login.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->route('login')->with('success', 'E-mail confirmado com sucesso! Agora é só entrar.');
    }

    //Reenviar o link de confirmação
    public function resend(Request $request)
    {
        $email = $request->input('email') ?: session('verify_email');

        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user && !$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }
        }

        return back()
            ->with('status', 'Se o e-mail existir e ainda não estiver confirmado, reenviamos o link.')
            ->with('verify_email', $email);
    }
}

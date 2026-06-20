<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Paginator::useBootstrapFour();

        //E-mail de redefinição de senha em português
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Nexo Estoque — Redefinição de senha')
                ->greeting('Olá!')
                ->line('Recebemos um pedido para redefinir a senha da sua conta no Nexo Estoque.')
                ->action('Redefinir minha senha', $url)
                ->line('Este link expira em 60 minutos.')
                ->line('Se não foi você que pediu, pode ignorar este e-mail com segurança.')
                ->salutation('— Equipe Nexo Estoque');
        });

        //E-mail de confirmação de cadastro em português
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Nexo Estoque — Confirme seu e-mail')
                ->greeting('Bem-vindo(a) ao Nexo Estoque!')
                ->line('Falta só um passo para ativar sua conta: confirme seu e-mail clicando no botão abaixo.')
                ->action('Confirmar meu e-mail', $url)
                ->line('Se você não criou esta conta, pode ignorar este e-mail.')
                ->salutation('— Equipe Nexo Estoque');
        });
    }
}

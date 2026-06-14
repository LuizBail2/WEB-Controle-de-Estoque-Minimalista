<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Auth\Notifications\ResetPassword;
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
    }
}

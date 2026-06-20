@extends('layouts.guest')

@section('title', 'Nexo Estoque — Confirme seu e-mail')

@section('content')
<div class="auth-card">
  <div class="auth-form">
    <div class="auth-brand">
      <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Nexo Estoque"></div>
      <span class="auth-brand__name">Nexo Estoque</span>
    </div>
    <h1 class="auth-title">Confirme seu e-mail ✉️</h1>
    <p class="auth-sub" style="max-width:400px;">
    @if(!empty($email))
        Enviamos um link de confirmação para <strong>{{ $email }}</strong>.
    @else
        Enviamos um link de confirmação para o seu e-mail.
    @endif
    Clique no link do e-mail para ativar sua conta. (Confira também a caixa de spam.)
</p>

    @if(session('status'))
      <div class="auth-error" style="background:rgba(34,197,94,.18);border-color:rgba(187,247,208,.5);">
        <span>✓</span><span>{{ session('status') }}</span>
      </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:8px;">
      @csrf
      <input type="hidden" name="email" value="{{ $email }}">
      <button class="auth-btn" type="submit">Reenviar e-mail de confirmação</button>
    </form>

    <div class="auth-foot" style="margin-top:20px;">
      Já confirmou? <a href="{{ route('login') }}">Fazer login</a>
    </div>
  </div>

  <div class="auth-aside">
    <div class="auth-aside__head">
      <h2>Quase lá!</h2>
      <p>Confirme o e-mail e comece a usar o Nexo Estoque.</p>
    </div>
    <div class="auth-aside__img"><img src="{{ asset('images/login-illustration.png') }}" alt="Gestão de estoque"></div>
  </div>
</div>
@endsection

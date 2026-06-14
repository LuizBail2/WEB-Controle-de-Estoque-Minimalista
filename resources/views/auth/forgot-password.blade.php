@extends('layouts.guest')

@section('title', 'Nexo Estoque — Redefinir senha')

@section('content')
<div class="auth-card">
  <div class="auth-form">
    <div class="auth-brand">
      <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Nexo Estoque"></div>
      <span class="auth-brand__name">Nexo Estoque</span>
    </div>
    <h1 class="auth-title">Esqueceu a senha?</h1>
    <p class="auth-sub">Digite seu e-mail e enviaremos um link para redefinir.</p>

    <form method="POST" action="{{ route('password.email') }}">
      @csrf

      @if(session('status'))
        <div class="auth-error" style="background:rgba(34,197,94,.18);border-color:rgba(187,247,208,.5);">
          <span>✓</span><span>{{ session('status') }}</span>
        </div>
      @endif

      @if($errors->any())
        <div class="auth-error"><span>⚠️</span><span>{{ $errors->first() }}</span></div>
      @endif

      <div class="auth-field">
        <label>Email</label>
        <div class="auth-input-wrap">
          <span class="ic">✉️</span>
          <input class="auth-input @error('email') error @enderror"
                 name="email" type="email" placeholder="seu@email.com"
                 value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
      </div>

      <button class="auth-btn" type="submit" style="margin-top:8px;">Enviar link →</button>

      <div class="auth-foot">Lembrou? <a href="{{ route('login') }}">Voltar ao login</a></div>
    </form>
  </div>

  <div class="auth-aside">
    <div class="auth-aside__head">
      <h2>Controle total do seu <span>estoque de peças</span></h2>
      <p>Do recebimento à saída, tudo em um só lugar.</p>
    </div>
    <div class="auth-aside__img"><img src="{{ asset('images/login-illustration.png') }}" alt="Gestão de estoque"></div>
  </div>
</div>
@endsection

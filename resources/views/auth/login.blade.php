@extends('layouts.guest')

@section('title', 'Nexo Estoque — Login')

@section('content')
<div class="auth-card">
  <div class="auth-form">
    <div class="auth-brand">
      <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Nexo Estoque"></div>
      <span class="auth-brand__name">Nexo Estoque</span>
    </div>
    <h1 class="auth-title">Faça o login</h1>
    <p class="auth-sub">Acesse sua conta para continuar</p>

    <form method="POST" action="{{ route('login.perform') }}">
      @csrf

      @if($errors->any())
        <div class="auth-error"><span>⚠️</span><span>{{ $errors->first() }}</span></div>
      @endif

      <div class="auth-field">
        <label>Email</label>
        <div class="auth-input-wrap">
          <span class="ic">✉️</span>
          <input class="auth-input @error('email') error @enderror"
                 name="email" type="email" placeholder="seu@email.com"
                 value="{{ old('email') }}" autocomplete="email" required>
        </div>
      </div>

      <div class="auth-field">
        <label>Senha</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input @error('password') error @enderror"
                 id="loginPassword" name="password" type="password" placeholder="••••••••"
                 autocomplete="current-password" required>
          <button class="auth-eye" type="button" tabindex="-1" onclick="togglePass('loginPassword', this)">👁</button>
        </div>
      </div>

      <div class="auth-row"><a href="{{ route('password.request') }}" class="auth-forgot">Esqueci a senha</a></div>

      <button class="auth-btn" type="submit">Entrar →</button>

      <div class="auth-foot">Não tem conta? <a href="{{ route('register') }}">Cadastre-se</a></div>
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

<script>
function togglePass(id, btn) {
  const i = document.getElementById(id);
  const p = i.type === 'password';
  i.type = p ? 'text' : 'password';
  btn.textContent = p ? '🙈' : '👁';
}
</script>
@endsection

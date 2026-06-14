@extends('layouts.guest')

@section('title', 'Nexo Estoque — Criar conta')

@section('content')
<div class="auth-card">
  <div class="auth-form">
    <div class="auth-brand">
      <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Nexo Estoque"></div>
      <span class="auth-brand__name">Nexo Estoque</span>
    </div>
    <h1 class="auth-title">Criar conta</h1>
    <p class="auth-sub">Comece a gerenciar seu estoque</p>

    <form method="POST" action="{{ route('register.perform') }}">
      @csrf

      <div class="auth-field">
        <label>Nome</label>
        <div class="auth-input-wrap">
          <span class="ic">👤</span>
          <input class="auth-input @error('name') error @enderror"
                 name="name" type="text" placeholder="Seu nome"
                 value="{{ old('name') }}" autocomplete="name" required>
        </div>
        @error('name')<div class="auth-fielderror">{{ $message }}</div>@enderror
      </div>

      <div class="auth-field">
        <label>Email</label>
        <div class="auth-input-wrap">
          <span class="ic">✉️</span>
          <input class="auth-input @error('email') error @enderror"
                 name="email" type="email" placeholder="seu@email.com"
                 value="{{ old('email') }}" autocomplete="email" required>
        </div>
        @error('email')<div class="auth-fielderror">{{ $message }}</div>@enderror
      </div>

      <div class="auth-field">
        <label>Senha</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input @error('password') error @enderror"
                 id="regPass" name="password" type="password" placeholder="••••••••"
                 autocomplete="new-password" required>
          <button class="auth-eye" type="button" tabindex="-1" onclick="togglePass('regPass', this)">👁</button>
        </div>
        @error('password')<div class="auth-fielderror">{{ $message }}</div>@enderror
      </div>

      <div class="auth-field">
        <label>Confirmar senha</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input"
                 id="regPass2" name="password_confirmation" type="password" placeholder="••••••••"
                 autocomplete="new-password" required>
          <button class="auth-eye" type="button" tabindex="-1" onclick="togglePass('regPass2', this)">👁</button>
        </div>
      </div>

      <button class="auth-btn" type="submit" style="margin-top:8px;">Criar conta →</button>

      <div class="auth-foot">Já tem conta? <a href="{{ route('login') }}">Entrar</a></div>
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

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
    <p class="auth-sub">Crie a sua empresa ou entre em uma existente</p>

    @php $mode = old('mode', 'create'); @endphp

    {{-- Seletor de modo --}}
    <div class="reg-modes">
      <button type="button" class="reg-mode {{ $mode === 'create' ? 'is-active' : '' }}" data-mode="create">🏢 Criar empresa</button>
      <button type="button" class="reg-mode {{ $mode === 'join' ? 'is-active' : '' }}" data-mode="join">👥 Entrar numa empresa</button>
    </div>

    <form method="POST" action="{{ route('register.perform') }}">
      @csrf
      <input type="hidden" name="mode" id="regMode" value="{{ $mode }}">

      @if($errors->any())
        <div class="auth-error"><span>⚠️</span><span>{{ $errors->first() }}</span></div>
      @endif

      <div class="auth-field">
        <label>Seu nome</label>
        <div class="auth-input-wrap">
          <span class="ic">👤</span>
          <input class="auth-input" name="name" type="text" placeholder="Seu nome"
                 value="{{ old('name') }}" autocomplete="name" required>
        </div>
      </div>

      <div class="auth-field">
        <label>Email</label>
        <div class="auth-input-wrap">
          <span class="ic">✉️</span>
          <input class="auth-input" name="email" type="email" placeholder="seu@email.com"
                 value="{{ old('email') }}" autocomplete="email" required>
        </div>
      </div>

      {{-- Nome da empresa: rótulo muda conforme o modo --}}
      <div class="auth-field">
        <label id="companyLabel">{{ $mode === 'join' ? 'Nome da empresa (onde você vai trabalhar)' : 'Nome da sua empresa' }}</label>
        <div class="auth-input-wrap">
          <span class="ic">🏢</span>
          <input class="auth-input" name="company_name" type="text"
                 placeholder="Ex: Loja do João" value="{{ old('company_name') }}" required>
        </div>
        <div class="reg-hint" id="joinHint" style="{{ $mode === 'join' ? '' : 'display:none;' }}">
          Digite o nome exatamente como o administrador cadastrou. Você entrará como pendente até ele aprovar.
        </div>
      </div>

      {{-- CNPJ: só no modo criar empresa --}}
      <div class="auth-field" id="cnpjField" style="{{ $mode === 'create' ? '' : 'display:none;' }}">
        <label>CNPJ da empresa</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input" name="cnpj" id="regCnpj" type="text"
                 inputmode="numeric" placeholder="00.000.000/0000-00" value="{{ old('cnpj') }}">
        </div>
        <div class="reg-hint">Guardado de forma criptografada e segura.</div>
      </div>

      <div class="auth-field">
        <label>Senha</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input" id="regPass" name="password" type="password" placeholder="••••••••"
                 autocomplete="new-password" required>
          <button class="auth-eye" type="button" tabindex="-1" onclick="togglePass('regPass', this)">👁</button>
        </div>
      </div>

      <div class="auth-field">
        <label>Confirmar senha</label>
        <div class="auth-input-wrap">
          <span class="ic">🔒</span>
          <input class="auth-input" id="regPass2" name="password_confirmation" type="password" placeholder="••••••••"
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
      <h2>Controle total do seu <span>estoque</span></h2>
      <p>Do recebimento à saída, tudo em um só lugar.</p>
    </div>
    <div class="auth-aside__img"><img src="{{ asset('images/login-illustration.png') }}" alt="Gestão de estoque"></div>
  </div>
</div>

<style>
  .reg-modes{ display:flex; gap:8px; margin-bottom:18px; }
  .reg-mode{
    flex:1; padding:10px 8px; border-radius:12px; cursor:pointer;
    border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.08);
    color:#fff; font-weight:700; font-size:.88rem; transition:.15s;
  }
  .reg-mode.is-active{ background:#fff; color:#1e293b; border-color:#fff; }
  .reg-hint{ font-size:.78rem; color:rgba(255,255,255,.7); margin-top:6px; line-height:1.4; }
</style>

<script>
function togglePass(id, btn) {
  const i = document.getElementById(id);
  const p = i.type === 'password';
  i.type = p ? 'text' : 'password';
  btn.textContent = p ? '🙈' : '👁';
}

(function(){
  const modeInput = document.getElementById('regMode');
  const cnpjField = document.getElementById('cnpjField');
  const cnpjInput = document.getElementById('regCnpj');
  const companyLabel = document.getElementById('companyLabel');
  const joinHint = document.getElementById('joinHint');

  document.querySelectorAll('.reg-mode').forEach(function(btn){
    btn.addEventListener('click', function(){
      const mode = btn.dataset.mode;
      document.querySelectorAll('.reg-mode').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      modeInput.value = mode;

      if (mode === 'create') {
        cnpjField.style.display = '';
        cnpjInput.required = true;
        companyLabel.textContent = 'Nome da sua empresa';
        joinHint.style.display = 'none';
      } else {
        cnpjField.style.display = 'none';
        cnpjInput.required = false;
        companyLabel.textContent = 'Nome da empresa (onde você vai trabalhar)';
        joinHint.style.display = '';
      }
    });
  });

  //máscarade CNPJ
  if (cnpjInput) {
    cnpjInput.addEventListener('input', function(){
      let v = cnpjInput.value.replace(/\D/g, '').slice(0, 14);
      v = v.replace(/^(\d{2})(\d)/, '$1.$2')
           .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
           .replace(/\.(\d{3})(\d)/, '.$1/$2')
           .replace(/(\d{4})(\d)/, '$1-$2');
      cnpjInput.value = v;
    });
  }

  //estado inicial do  CNPJ
  if (cnpjInput) cnpjInput.required = (modeInput.value === 'create');
})();
</script>
@endsection

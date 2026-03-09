@extends('layouts.guest')

@section('title', 'StockPro — Login')

@section('content')

<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>
<div class="bg-grid"></div>

<div class="container">

  <div class="left-panel">
    <div class="brand">
      <div class="brand-icon">📦</div>
      <div class="brand-name">Gerenciador<span> de Estoque</span></div>
    </div>

    <div class="left-hero">
      <div class="left-title">Gerencie seu<br>estoque com <span>precisão</span></div>
      <div class="left-sub">Controle entradas, saídas e alertas<br>de estoque em tempo real.</div>
    </div>

    <div class="features">
      <div class="feature-item">
        <div class="feature-icon" style="background:rgba(34,211,160,.12)">📊</div>
        <div class="feature-text">
          <strong>Dashboard completo</strong>
          Visualize métricas e KPIs de estoque
        </div>
      </div>

      <div class="feature-item">
        <div class="feature-icon" style="background:rgba(245,200,66,.12)">⚠️</div>
        <div class="feature-text">
          <strong>Alertas automáticos</strong>
          Notificações de estoque baixo e vencimento
        </div>
      </div>

      <div class="feature-item">
        <div class="feature-icon" style="background:rgba(79,142,247,.12)">↔️</div>
        <div class="feature-text">
          <strong>Histórico de movimentações</strong>
          Rastreie cada entrada e saída com detalhes
        </div>
      </div>
    </div>
  </div>

  <div class="right-panel">
    <div class="login-header">
      <div class="login-icon">🔐</div>
      <div class="login-title">Faça o login</div>
      <div class="login-sub">Acesse sua conta para continuar</div>
    </div>

    <form class="form" method="POST" action="{{ route('login.perform') }}">
      @csrf

      @if($errors->any())
        <div class="error-msg show">
          <span>⚠️</span>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <div class="form-field">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input
            class="form-input @error('email') error @enderror"
            name="email"
            type="email"
            placeholder="seu@email.com"
            value="{{ old('email') }}"
            autocomplete="email"
            required
          >
        </div>
      </div>

      <div class="form-field">
        <label class="form-label">Senha</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input
            class="form-input @error('password') error @enderror"
            id="loginPassword"
            name="password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
          <button class="input-toggle" onclick="togglePass('loginPassword', this)" tabindex="-1" type="button">👁</button>
        </div>
      </div>

      <div class="form-row">
        
        {{-- <a href="#" class="forgot">Esqueci a senha</a> --}}
      </div>

      <button class="btn-login" type="submit">
        <div class="btn-text">Entrar →</div>
        <div class="spinner"></div>
      </button>

    </form>
  </div>
</div>

<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  const isPass = inp.type === 'password';
  inp.type = isPass ? 'text' : 'password';
  btn.textContent = isPass ? '👁' : '👁';
}
</script>

@endsection
@extends('layouts.guest')

@section('title', 'Nexo Estoque — Aguardando aprovação')

@section('content')
<div class="auth-card">
  <div class="auth-form">
    <div class="auth-brand">
      <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Nexo Estoque"></div>
      <span class="auth-brand__name">Nexo Estoque</span>
    </div>
    <h1 class="auth-title">Cadastro enviado! ⏳</h1>
    <p class="auth-sub" style="max-width:380px;">
      Seu cadastro foi enviado para o administrador. Assim que ele aprovar seu acesso, você recebe
      um e-mail e já poderá entrar. Pode fechar esta página enquanto isso.
    </p>

    <div style="background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.18);
                border-radius:12px; padding:14px 16px; font-size:.9rem; color:rgba(255,255,255,.85);
                max-width:380px; line-height:1.5;">
      ✉️ Você será avisado no seu e-mail quando for <strong>aprovado</strong> ou <strong>recusado</strong>.
    </div>

    <div class="auth-foot" style="margin-top:24px;">
      <a href="{{ route('login') }}">Voltar ao login</a>
    </div>
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

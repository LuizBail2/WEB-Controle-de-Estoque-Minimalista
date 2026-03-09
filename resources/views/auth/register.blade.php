@extends('layouts.app')

@section('title','Registrar')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div style="width: 100%; max-width: 520px;">
    <div class="text-center mb-3">
        <img src="{{ asset('images/logo.png') }}"
         alt="Logo Stock Manager"
         style="width: 90px; height: 90px; object-fit: contain;">
</div>

<div style="display:flex; justify-content:center; margin-top:28px;">
  <div style="width:420px; box-shadow:0 8px 24px rgba(15,23,42,0.08); border-radius:8px; overflow:hidden; background:#fff;">
    <div style="padding:22px 24px;">
      <h2 style="margin-bottom:6px;">Criar conta</h2>
      
      <form method="POST" action="{{ route('register.perform') }}">
        @csrf
        <div style="margin-bottom:12px">
          <label style="display:block; margin-bottom:6px; color:#334155">Nome</label>
          <input name="name" type="text" value="{{ old('name') }}" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff">
          @error('name') <div style="color:#ef4444; font-size:13px; margin-top:8px;">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom:12px">
          <label style="display:block; margin-bottom:6px; color:#334155">Email</label>
          <input name="email" type="email" value="{{ old('email') }}" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff;">
          @error('email') <div style="color:#ef4444; font-size:13px; margin-top:8px;">{{ $message }}</div> @enderror
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px">
          <div style="flex:1; min-width:140px">
            <label style="display:block; margin-bottom:6px; color:#334155">Senha</label>
            <input name="password" type="password" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff">
            @error('password') <div style="color:#ef4444; font-size:13px; margin-top:8px;">{{ $message }}</div> @enderror
          </div>

          <div style="flex:1; min-width:140px">
            <label style="display:block; margin-bottom:6px; color:#334155">Confirmar senha</label>
            <input name="password_confirmation" type="password" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff;">
          </div>
        </div>

        <div style="margin-top:8px; display:flex; gap:8px; justify-content:space-between; align-items:center;">
          <a class="btn" href="{{ route('login') }}" style="text-decoration:none; padding:10px 14px; border-radius:6px; border:1px solid #0606bdff; background:#fff;">Voltar</a>
          <button type="submit" class="btn" style="background:#0b63ff; color:#fff; padding:10px 18px; border-radius:6px; border:none;">Registrar</button>
        </div>
      </form>

      <div style="margin-top:12px; color:#6b7280; font-size:13px;">
        Já tem conta? <a href="{{ route('login') }}">Entrar</a>
      </div>
    </div>
  </div>
</div>
@endsection

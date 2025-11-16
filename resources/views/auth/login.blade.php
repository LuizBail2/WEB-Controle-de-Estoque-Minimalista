@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">

        <div class="card shadow">
            <div class="card-body">
                <h3 class="mb-3">Faça o login</h3>

                <form method="POST" action="{{ route('login.perform') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" value="{{ old('email') }}" class="form-control" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff;">
                        @error('email')
                          <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input name="password" type="password" class="form-control" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #0606bdff;">
                    </div>

                    <button class="btn btn-primary w-100">Entrar</button>
                </form>

                <div style="margin-top:20px" class="muted">
                 Não tem conta? <a href="{{ route('register') }}">Criar conta</a>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

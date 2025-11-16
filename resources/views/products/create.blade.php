@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')

<h3 class="mb-3">Novo Produto</h3>

<form method="POST" action="{{ route('products.store') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Categoria</label>
        <input type="text" name="category" class="form-control">
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantity" class="form-control" value="0" min="0" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Quantidade mínima</label>
            <input type="number" name="minimum_quantity" class="form-control" value="0" min="0" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Preço (opcional)</label>
            <input type="number" name="price" class="form-control" step="0.01">
        </div>
    </div>

    <button class="btn btn-primary">Salvar</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>

</form>

@endsection

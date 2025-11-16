@extends('layouts.app')

@section('title', 'Editar Produto')

@section('content')

<h3 class="mb-3">Editar Produto</h3>

<form method="POST" action="{{ route('products.update', $product) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="name" value="{{ $product->name }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Categoria</label>
        <input type="text" name="category" value="{{ $product->category }}" class="form-control">
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantity" value="{{ $product->quantity }}" min="0" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Quantidade mínima</label>
            <input type="number" name="minimum_quantity" value="{{ $product->minimum_quantity }}" min="0" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Preço</label>
            <input type="number" name="price" value="{{ $product->price }}" step="0.01" class="form-control">
        </div>
    </div>

    <button class="btn btn-primary">Salvar alterações</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>

</form>

@endsection

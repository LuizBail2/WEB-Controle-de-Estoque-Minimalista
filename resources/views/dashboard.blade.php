@extends('layouts.app')

@section('title', 'Gerenciador de Eletrônicos')

@section('content')

<h2 class="mb-4">Dashboard</h2>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card p-3 shadow-sm">
            <h6>Total de produtos</h6>
            <h2>{{ \App\Models\Product::count() }}</h2>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card p-3 shadow-sm">
            <h6>Estoque baixo</h6>
            <h2>{{ \App\Models\Product::whereColumn('quantity','<=','minimum_quantity')->count() }}</h2>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card p-3 shadow-sm">
            <a href="{{ route('products.create') }}" class="btn btn-primary w-100 mt-2">Novo Produto</a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100 mt-2">Ver Produtos</a>
        </div>
    </div>
</div>

@endsection

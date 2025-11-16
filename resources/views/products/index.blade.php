@extends('layouts.app')

@section('title', 'Gerenciador de Eletrônicos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-5">
    <h3>Produtos</h3>
    <a href="{{ route('products.create') }}" class="btn btn-success">Novo Produto</a>
</div>

<table class="table table-striped shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Qtd</th>
            <th>Mínimo</th>
            <th>Preço</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>

    
    @foreach($products as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td>{{ $p->category }}</td>
            <td>{{ $p->quantity }}</td>
            <td>{{ $p->minimum_quantity }}</td>
            <td>
                @if($p->price)
                    R$ {{ number_format($p->price, 2, ',', '.') }}
                @else
                    -
                @endif
            </td>
            <td>
                @if($p->quantity <= $p->minimum_quantity)
                    <span class="badge bg-danger">Baixo</span>
                @else
                    <span class="badge bg-success">OK</span>
                @endif
            </td>
            <td>
                <a href="{{ route('products.edit', $p) }}" class="btn btn-sm btn-primary">Editar</a>

                <form action="{{ route('products.destroy', $p) }}" class="d-inline" method="POST" onsubmit="return confirm('Excluir produto?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">Excluir</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

 <div style="display:flex; justify-content:space-between; margin-bottom:30px;">
    <a href="{{ route('dashboard') }}" class="btn">Voltar para o Home</a>
</div>

<div class="mt-3">
    {{ $products->links() }}
</div>

    <span></span>

@endsection

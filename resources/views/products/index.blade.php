@extends('layouts.app')

@section('title', 'Gerenciador de Eletrônicos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-5">
    <h3>Produtos</h3>
    <a href="{{ route('products.create') }}" class="btn btn-success">Novo Produto</a>
</div>

<!-- TABELA (desktop + tablet). Permite scroll horizontal em telas pequenas -->
<div class="table-responsive desktop-only shadow-sm mb-3">
    <table class="table table-striped table-hover table-min">
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
        @forelse($products as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->category ?? '-' }}</td>
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
        @empty
            <tr>
                <td colspan="7">Nenhum produto encontrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- CARTÕES (mobile) — mais legível em telas pequenas -->
<div class="mobile-only">
    @forelse($products as $p)
        <div class="card mb-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title mb-1">{{ $p->name }}</h5>
                        <p class="mb-1 small text-muted">Categoria: {{ $p->category ?? '-' }}</p>
                        <p class="mb-1 small">Qtd: <strong>{{ $p->quantity }}</strong> • Mín: <strong>{{ $p->minimum_quantity }}</strong></p>
                        <p class="mb-0 small">Preço: 
                            @if($p->price)
                                R$ {{ number_format($p->price, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </p>
                    </div>

                    <div style="min-width:86px; text-align:right;">
                        @if($p->quantity <= $p->minimum_quantity)
                            <span class="badge bg-danger">Baixo</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('products.edit', $p) }}" class="btn btn-sm btn-primary flex-fill">Editar</a>

                    <form action="{{ route('products.destroy', $p) }}" method="POST" class="d-inline flex-fill" onsubmit="return confirm('Excluir produto?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger w-100">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="text-muted">Nenhum produto encontrado.</div>
    @endforelse
</div>

<!-- Ações / voltar -->
<div class="d-flex justify-content-between align-items-center my-4">
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">← Voltar para o Home</a>

    <!-- paginação à direita -->
    <div>
        {{ $products->links() }}
    </div>
</div>

@endsection
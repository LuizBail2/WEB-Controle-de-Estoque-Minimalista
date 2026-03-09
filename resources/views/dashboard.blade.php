@extends('layouts.app')

@section('title', 'Gerenciador de Eletrônicos')

@section('content')


<div class="page-header">
    <div>
        <div class="page-title">Dashboard</div>
    </div>

    <div class="header-actions">
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Ver Produtos</a>
        <a class="btn btn-primary" href="{{ route('products.create') }}">+ Novo Produto</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-header">
            <span class="stat-label">Total de Produtos</span>
            <span class="stat-chip chip-blue">INFO</span>
        </div>
        <div class="stat-value">{{ $totalProducts }}</div>
        <div class="stat-footer">Itens cadastrados</div>
        <div class="stat-icon">📦</div>
    </div>

    <div class="stat-card yellow">
        <div class="stat-header">
            <span class="stat-label">Estoque Baixo</span>
            <span class="stat-chip chip-yellow">ATENÇÃO</span>
        </div>
        <div class="stat-value" style="color:var(--yellow)">{{ $lowStockCount }}</div>
        <div class="stat-footer">Reposição recomendada</div>
        <div class="stat-icon">⚠️</div>
    </div>

    <div class="stat-card red">
        <div class="stat-header">
            <span class="stat-label">Sem Estoque</span>
            <span class="stat-chip chip-red">CRÍTICO</span>
        </div>
        <div class="stat-value" style="color:var(--red)">{{ $outOfStockCount }}</div>
        <div class="stat-footer">Reposição urgente</div>
        <div class="stat-icon">❌</div>
    </div>
</div> 


<div class="catalog-wrap">
  <div class="catalog-card">
    <div class="catalog-head">
      <div>
        <p class="catalog-title">Catálogo de Produtos</p>
        <div class="catalog-sub">Últimos itens cadastrados</div>
      </div>

      <form method="GET" action="{{ route('dashboard') }}" class="catalog-filters">
        <input type="text"
               name="search"
               placeholder="🔍Buscar produto..."
               value="{{ request('search') }}"
               class="catalog-input">

        <select name="category" class="catalog-select">
            <option value="">Todas as categorias</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
            @endforeach
        </select>

        <button class="btn btn-secondary" type="submit">Buscar</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="catalog-table">
        <thead>
          <tr>
            <th>Produto</th>
            <th>Categoria</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
        @forelse($products as $p)
          <tr>
            <td style="font-weight:800;">{{ $p->name }}</td>
            <td style="color:#6b7280;">{{ $p->category ?? '-' }}</td>
            <td>
              @if($p->quantity == 0)
                <span class="badge-soft badge-out">Sem estoque</span>
              @elseif($p->quantity <= $p->minimum_quantity)
                <span class="badge-soft badge-low">Estoque baixo</span>
              @else
                <span class="badge-soft badge-ok">Em estoque</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="color:#6b7280; padding:14px 16px;">
              Nenhum produto cadastrado ainda.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
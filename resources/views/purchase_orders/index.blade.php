@extends('layouts.app')

@section('title', 'Pedidos de Compra')

@php
    $statusBadge = function ($s) {
        return match ($s) {
            'recebido'  => '<span class="po-badge po-badge--green">✓ Recebido</span>',
            'cancelado' => '<span class="po-badge po-badge--red">✕ Cancelado</span>',
            default     => '<span class="po-badge po-badge--yellow">⏳ Pendente</span>',
        };
    };
@endphp

@section('content')
<div class="reports-dark po">

    <div class="po-header">
        <div>
            <h3 class="po-title">Pedidos de Compra</h3>
            <p class="po-sub">Fornecedor → Pedido → Recebimento → Entrada no estoque</p>
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">+ Novo Pedido</a>
    </div>

    <form method="GET" action="{{ route('purchase-orders.index') }}" class="po-toolbar">
        <div class="rp-search">
            <span class="rp-search__ico">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" class="rp-input" placeholder="Buscar por código ou fornecedor...">
        </div>
        <select name="status" class="rp-sel" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <option value="pendente"  {{ request('status') === 'pendente'  ? 'selected' : '' }}>Pendentes</option>
            <option value="recebido"  {{ request('status') === 'recebido'  ? 'selected' : '' }}>Recebidos</option>
            <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
        </select>
    </form>

    <div class="rp-report">
        <div class="table-responsive">
            <table class="table rp-table align-middle mb-0" data-mob="cards">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fornecedor</th>
                        <th class="text-center">Itens</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr>
                            <td data-label="Pedido"><strong>{{ $o->code }}</strong></td>
                            <td data-label="Fornecedor">{{ $o->supplier->name ?? '—' }}</td>
                            <td class="text-center" data-label="Itens">{{ $o->items_count }}</td>
                            <td class="text-end" data-label="Total"><span class="rp-money">R$ {{ number_format($o->total, 2, ',', '.') }}</span></td>
                            <td data-label="Status">{!! $statusBadge($o->status) !!}</td>
                            <td data-label="Data">{{ $o->created_at->format('d/m/Y') }}</td>
                            <td class="text-end" data-label="Ações">
                                <a href="{{ route('purchase-orders.show', $o) }}" class="act-btn act-view"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--t-muted)">Nenhum pedido de compra ainda. Clique em “+ Novo Pedido”.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rp-pagination">
            <div class="rp-pag-info">Mostrando {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} de {{ $orders->total() }}</div>
            <div>{{ $orders->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

<style>
    .po{ color: var(--t-text); }
    .po-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .po-title{ margin:0; font-weight:800; } .po-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .po-toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:12px; margin-bottom:16px; }
    .rp-search{ position:relative; flex:1; min-width:200px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:40px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-sel{ height:40px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text);  cursor:pointer; }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; overflow:hidden; }
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; white-space:nowrap; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }
    .rp-money{ color:#22c55e; font-weight:600; }

    .po-badge{ display:inline-block; padding:3px 10px; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; }
    .po-badge--green{ background:rgba(34,197,94,.15); color:#22c55e; }
    .po-badge--yellow{ background:rgba(245,200,66,.15); color:#ffb700; }
    .po-badge--red{ background:rgba(239,68,68,.15); color:#ef4444; }

    .act-btn{ padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1px solid var(--t-input-border); display:inline-flex; align-items:center; gap:5px; text-decoration:none; background: var(--t-panel-2); color: var(--t-text); }
    .act-btn:hover{ border-color: var(--accent, #3b82f6); }

    .rp-pagination{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; flex-wrap:wrap; gap:10px; }
    .rp-pag-info{ color: var(--t-muted); font-size:.85rem; }
    .rp-pagination .pagination{ margin:0; }
    .rp-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .rp-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .rp-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }
</style>

<script>
(function(){
  var input = document.querySelector('input[name="search"]');
  var tbody = document.querySelector('.rp-table tbody');
  if(!input || !tbody) return;
  function norm(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
  var emptyRow = null;
  input.addEventListener('input', function(){
    var q = norm(input.value.trim()), shown = 0;
    Array.prototype.forEach.call(tbody.children, function(tr){
      if (tr === emptyRow) return;
      var match = norm(tr.textContent).indexOf(q) !== -1;
      tr.style.display = (q === '' || match) ? '' : 'none';
      if (q === '' || match) shown++;
    });
    if (q !== '' && shown === 0){
      if (!emptyRow){
        emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="99" style="text-align:center;padding:18px;color:var(--t-muted);">Nenhum resultado para "' + input.value + '".</td>';
        tbody.appendChild(emptyRow);
      } else { emptyRow.firstChild.textContent = 'Nenhum resultado para "' + input.value + '".'; }
      emptyRow.style.display = '';
    } else if (emptyRow){ emptyRow.style.display = 'none'; }
  });
})();
</script>
@endsection

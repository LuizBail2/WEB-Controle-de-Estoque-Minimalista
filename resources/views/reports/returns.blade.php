@extends('layouts.app')

@section('title', 'Devoluções')

@section('content')
<div class="reports-dark rp">

    <div class="rp-header">
        <div>
            <h3 class="rp-title">Devoluções</h3>
            <p class="rp-sub">Histórico de devoluções com documento para download</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-light btn-sm">← Voltar aos relatórios</a>
    </div>

    <form method="GET" action="{{ route('reports.returns') }}" class="rp-toolbar">
        <div class="rp-search">
            <span class="rp-search__ico">🔍</span>
            <input type="text" name="q" value="{{ request('q') }}" class="rp-input" placeholder="Buscar por produto, código ou motivo...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
    </form>

    <div class="rp-report">
        <div class="table-responsive">
            <table class="table rp-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Sentido</th>
                        <th class="text-center">Qtd</th>
                        <th>Motivo</th>
                        <th>Responsável</th>
                        <th class="text-end">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                {{ $m->product->name ?? '(removido)' }}
                                <div class="rp-muted">{{ $m->product->code ?? '' }}</div>
                            </td>
                            <td>
                                @if($m->direction === 'fornecedor')
                                    <span class="rp-badge rp-badge--red">Ao fornecedor</span>
                                @else
                                    <span class="rp-badge rp-badge--green">De cliente</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $m->quantity }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($m->reason, 60) ?: '—' }}</td>
                            <td>{{ $m->user->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('movements.document', $m) }}" target="_blank" class="rp-doc-btn">📄 PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--t-muted)">Nenhuma devolução registrada ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rp-pagination">
            <div class="rp-pag-info">Mostrando {{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }} de {{ $returns->total() }}</div>
            <div>{{ $returns->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

<style>
    .rp{ color: var(--t-text); }
    .rp-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .rp-title{ margin:0; font-weight:800; } .rp-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .rp-toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:12px; margin-bottom:16px; }
    .rp-search{ position:relative; flex:1; min-width:200px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:40px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; overflow:hidden; }
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; white-space:nowrap; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }
    .rp-muted{ color: var(--t-muted); font-size:.78rem; }

    .rp-badge{ display:inline-block; padding:3px 10px; border-radius:999px; font-size:.74rem; font-weight:600; white-space:nowrap; }
    .rp-badge--red{ background:rgba(239,68,68,.15); color:#ef4444; }
    .rp-badge--green{ background:rgba(34,197,94,.15); color:#22c55e; }

    .rp-doc-btn{ padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; border:1px solid var(--t-input-border); background: var(--t-panel-2); color: var(--t-text); }
    .rp-doc-btn:hover{ border-color: var(--accent, #3b82f6); }

    .rp-pagination{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; flex-wrap:wrap; gap:10px; }
    .rp-pag-info{ color: var(--t-muted); font-size:.85rem; }
    .rp-pagination .pagination{ margin:0; }
    .rp-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .rp-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .rp-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }
</style>

<script>
(function(){
  var input = document.querySelector('input[name="q"]');
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

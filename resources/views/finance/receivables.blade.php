@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0 fw-bold">Contas a Receber</h2>
        <a href="{{ route('finance.index') }}" class="btn btn-outline-light btn-sm">← Financeiro</a>
    </div>

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="card bg-dark border-secondary-subtle mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.receivables.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label small text-secondary">Descrição *</label>
                    <input name="description" class="form-control bg-dark text-light border-secondary" required value="{{ old('description') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-secondary">Valor (R$) *</label>
                    <input name="amount" type="number" step="0.01" min="0.01" class="form-control bg-dark text-light border-secondary" required value="{{ old('amount') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-secondary">Vencimento *</label>
                    <input name="due_date" type="date" class="form-control bg-dark text-light border-secondary" required value="{{ old('due_date') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-secondary">Cliente</label>
                    <input name="customer" class="form-control bg-dark text-light border-secondary" value="{{ old('customer') }}">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="btn-group btn-group-sm mb-3 flex-wrap" role="group">
        @foreach (['all' => 'Todos', 'open' => 'Em aberto', 'overdue' => 'Vencidas', 'received' => 'Recebidas'] as $k => $label)
            <a href="{{ route('finance.receivables', ['filter' => $k]) }}"
               class="btn {{ $filter === $k ? 'btn-primary' : 'btn-outline-light' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card bg-dark border-secondary-subtle">
        <div class="card-body p-0">
            <table class="table table-dark table-hover mb-0 align-middle responsive-table">
                <thead>
                    <tr>
                        <th>Descrição</th><th>Cliente</th><th class="text-end">Valor</th>
                        <th>Vencimento</th><th>Status</th><th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td data-label="Descrição">{{ $item->description }}</td>
                            <td data-label="Cliente" class="text-secondary">{{ $item->customer ?? '—' }}</td>
                            <td data-label="Valor" class="text-end">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
                            <td data-label="Vencimento">{{ $item->due_date->format('d/m/Y') }}</td>
                            <td data-label="Status">
                                @if ($item->received)
                                    <span class="badge text-bg-success">Recebido</span>
                                @elseif ($item->is_overdue)
                                    <span class="badge text-bg-danger">Vencida</span>
                                @else
                                    <span class="badge text-bg-secondary">Em aberto</span>
                                @endif
                            </td>
                            <td data-label="Ações" class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @unless ($item->received)
                                        <form method="POST" action="{{ route('finance.receivables.receive', $item) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-success">Receber</button>
                                        </form>
                                    @endunless
                                    <button type="button" class="btn btn-sm btn-outline-danger js-del-conta"
                                            data-name="{{ $item->description }}"
                                            data-url="{{ route('finance.receivables.destroy', $item) }}">×</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Nenhuma conta neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>

{{-- Modal de exclusão estilizado --}}
<div class="modal fade" id="delContaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content del-modal">
      <div class="del-ico">🗑️</div>
      <h5 class="del-title">Excluir conta?</h5>
      <p class="del-text">Tem certeza que deseja remover "<span id="delContaName">esta conta</span>"?<br>Esta ação não pode ser desfeita.</p>
      <div class="del-actions">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <form method="POST" id="delContaForm" action="#">
          @csrf @method('DELETE')
          <button type="submit" class="btn del-confirm">Sim, excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
    @media (max-width: 768px){
        .responsive-table thead{ display:none; }
        .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td{ display:block; width:100%; }
        .responsive-table tr{ background: var(--panel-2,#0b1220); border:1px solid var(--border,rgba(255,255,255,.10)); border-radius:12px; margin-bottom:12px; padding:8px 14px; }
        .responsive-table td{ display:flex; justify-content:space-between; align-items:center; gap:14px; border:0 !important; padding:9px 0 !important; text-align:right !important; background:transparent !important; }
        .responsive-table td + td{ border-top:1px solid var(--border,rgba(255,255,255,.06)) !important; }
        .responsive-table td::before{ content:attr(data-label); color:var(--muted,#94a3b8); font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; text-align:left; flex:0 0 auto; }
    }

    /* Modal de exclusão (mesmo padrão do produto) + blur forte */
    #delContaModal .modal-dialog{ max-width:420px; }
    .del-modal{ background:var(--panel,#111827); border:1px solid rgba(239,68,68,.35); border-radius:18px; padding:30px 26px; text-align:center; color:var(--text,#e5e7eb); }
    .del-ico{ font-size:42px; margin-bottom:10px; }
    .del-title{ font-weight:800; margin-bottom:8px; }
    .del-text{ color:var(--muted,#94a3b8); font-size:.92rem; margin-bottom:20px; }
    .del-actions{ display:flex; gap:10px; justify-content:center; align-items:center; }
    .del-actions form{ margin:0; }
    .del-confirm{ background:rgba(239,68,68,.15); color:#ef4444; border:1px solid rgba(239,68,68,.45); font-weight:600; }
    .del-confirm:hover{ background:rgba(239,68,68,.28); color:#ef4444; }
    .modal-backdrop.show{ z-index:1250 !important; background:rgba(8,12,22,.6) !important; opacity:1 !important; backdrop-filter:blur(7px); -webkit-backdrop-filter:blur(7px); }
    #delContaModal{ z-index:1260 !important; }
</style>

<script>
  document.querySelectorAll('.js-del-conta').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.getElementById('delContaName').textContent = btn.dataset.name || 'esta conta';
      document.getElementById('delContaForm').action = btn.dataset.url;
      if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById('delContaModal')).show();
    });
  });
</script>
@endsection

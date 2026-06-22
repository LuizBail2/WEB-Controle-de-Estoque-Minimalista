@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0 fw-bold">Contas a Pagar</h2>
        <a href="{{ route('finance.index') }}" class="fin-btn-nav">← Financeiro</a>
    </div>

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="fin-card mb-4">
        <div class="fin-card-body">
            <form method="POST" action="{{ route('finance.payables.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="fin-label">Descrição *</label>
                    <input name="description" class="fin-input" required value="{{ old('description') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="fin-label">Valor (R$) *</label>
                    <input name="amount" type="number" step="0.01" min="0.01" class="fin-input" required value="{{ old('amount') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="fin-label">Vencimento *</label>
                    <input name="due_date" type="date" class="fin-input" required value="{{ old('due_date') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="fin-label">Categoria</label>
                    <input name="category" class="fin-input" placeholder="Fornecedor, Despesa fixa..." value="{{ old('category') }}">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Adicionar</button>
                </div>
            </form>
            @error('amount') <div class="fin-neg small mt-2">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="btn-group btn-group-sm mb-3 flex-wrap" role="group">
        @foreach (['all' => 'Todos', 'open' => 'Em aberto', 'overdue' => 'Vencidas', 'paid' => 'Pagas'] as $k => $label)
            <a href="{{ route('finance.payables', ['filter' => $k]) }}"
               class="fin-btn-nav {{ $filter === $k ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="fin-card">
        <div class="fin-card-body p0">
            <table class="fin-tbl simple align-middle">
                <thead>
                    <tr>
                        <th>Descrição</th><th>Categoria</th><th class="t-end">Valor</th>
                        <th>Vencimento</th><th>Status</th><th class="t-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td data-label="Descrição">{{ $item->description }}</td>
                            <td data-label="Categoria" class="fin-muted">{{ $item->category ?? '—' }}</td>
                            <td data-label="Valor" class="t-end">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
                            <td data-label="Vencimento">{{ $item->due_date->format('d/m/Y') }}</td>
                            <td data-label="Status">
                                @if ($item->paid)
                                    <span class="badge text-bg-success">Pago</span>
                                @elseif ($item->is_overdue)
                                    <span class="badge text-bg-danger">Vencida</span>
                                @else
                                    <span class="badge text-bg-secondary">Em aberto</span>
                                @endif
                            </td>
                            <td data-label="Ações" class="t-end">
                                <div class="d-inline-flex gap-1">
                                    @unless ($item->paid)
                                        <form method="POST" action="{{ route('finance.payables.pay', $item) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-success">Pagar</button>
                                        </form>
                                    @endunless
                                    <button type="button" class="btn btn-sm btn-outline-danger js-del-conta"
                                            data-name="{{ $item->description }}"
                                            data-url="{{ route('finance.payables.destroy', $item) }}">×</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center fin-muted py-4">Nenhuma conta neste filtro.</td></tr>
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

@include('finance._styles')

<style>
    /* Modal de exclusão — tema-aware */
    #delContaModal .modal-dialog{ max-width:420px; }
    .del-modal{ background:var(--t-panel); border:1px solid rgba(239,68,68,.35); border-radius:18px; padding:30px 26px; text-align:center; color:var(--t-text); }
    .del-ico{ font-size:42px; margin-bottom:10px; }
    .del-title{ font-weight:800; margin-bottom:8px; }
    .del-text{ color:var(--t-muted); font-size:.92rem; margin-bottom:20px; }
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

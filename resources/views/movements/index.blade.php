@extends('layouts.app')
@section('title','Movimentações')

@section('content')
<div class="mov-dark">

<div class="stockpro-page">


  <div class="sp-header">
    <div>
      <h1 class="sp-title">Movimentações</h1>
      <div class="sp-sub">Histórico completo de entradas, saídas e ajustes de estoque</div>
    </div>

    <div class="sp-actions">
      {{-- você pode criar a rota depois --}}
      <a href="#" class="sp-btn">📄 Exportar CSV</a>

      <button type="button"
              class="sp-btn sp-btn-green"
              data-bs-toggle="modal"
              data-bs-target="#movModal"
              data-mov-type="entrada">
        ➕ Registrar Entrada
      </button>

      <button type="button"
              class="sp-btn sp-btn-red"
              data-bs-toggle="modal"
              data-bs-target="#movModal"
              data-mov-type="saida">
        ➖ Registrar Saída
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- CARDS --}}
  <div class="sp-cards">
    <div class="sp-card green">
      <div class="sp-card-top">
        <div class="sp-card-title">Entradas (mês)</div>
        <div class="sp-chip green">este mês</div>
      </div>
      <div class="sp-value">{{ $entradasMes ?? 0 }}</div>
      <div class="sp-meta">unidades</div>
    </div>

    <div class="sp-card red">
      <div class="sp-card-top">
        <div class="sp-card-title">Saídas (mês)</div>
        <div class="sp-chip red">este mês</div>
      </div>
      <div class="sp-value">{{ $saidasMes ?? 0 }}</div>
      <div class="sp-meta">unidades</div>
    </div>

    <div class="sp-card yellow">
      <div class="sp-card-top">
        <div class="sp-card-title">Ajustes</div>
        <div class="sp-chip yellow">este mês</div>
      </div>
      <div class="sp-value">{{ $ajustesMes ?? 0 }}</div>
      <div class="sp-meta">correções de inventário</div>
    </div>

    <div class="sp-card blue">
      <div class="sp-card-top">
        <div class="sp-card-title">Saldo líquido</div>
        <div class="sp-chip blue">unidades</div>
      </div>
      <div class="sp-value">
        {{ (($saldoLiquido ?? 0) >= 0 ? '+' : '') . ($saldoLiquido ?? 0) }}
      </div>
      <div class="sp-meta">Entradas − Saídas</div>
    </div>
  </div>


  <form method="GET" action="{{ route('movements.index') }}" class="sp-filters">

    <span class="sp-label">Tipo</span>
    <select name="type" class="sp-select">
      <option value="">Todos</option>
      @foreach(['entrada'=>'Entrada','saida'=>'Saída','ajuste'=>'Ajuste','transferencia'=>'Transferência','devolucao'=>'Devolução'] as $k=>$v)
        <option value="{{ $k }}" {{ request('type')==$k ? 'selected' : '' }}>{{ $v }}</option>
      @endforeach
    </select>

    <span class="sp-label">Produto</span>
    <select name="product_id" class="sp-select">
      <option value="">Todos</option>
      @foreach($products as $p)
        <option value="{{ $p->id }}" {{ request('product_id')==$p->id ? 'selected' : '' }}>
          {{ $p->name }}
        </option>
      @endforeach
    </select>

    <input name="q"
           value="{{ request('q') }}"
           class="sp-input"
           placeholder="Buscar movimentação...">

    <button class="sp-btn sp-btn-blue" type="submit">Filtrar</button>
    <a class="sp-btn" href="{{ route('movements.index') }}">Limpar filtros</a>
  </form>

  <div class="sp-panel">
    <div class="sp-panel-head">
      <h3 class="sp-panel-title">Histórico de Movimentações</h3>
      <div class="sp-panel-sub">Registros mais recentes</div>
    </div>

    <div class="table-responsive">
      <table class="sp-table">
        <thead>
          <tr>
            <th>Data / Hora</th>
            <th>Tipo</th>
            <th>Produto</th>
            <th>Qtd</th>
            <th>Valor Unit.</th>
            <th>Responsável</th>
            <th>Obs.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movements as $m)
            <tr>
              <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
              <td>{{ ucfirst($m->type) }}</td>
              <td>{{ $m->product->name ?? '-' }}</td>
              <td>{{ $m->quantity }}</td>
              <td>{{ $m->unit_price ? 'R$ '.number_format($m->unit_price,2,',','.') : '-' }}</td>
              <td>{{ $m->user->name ?? '—' }}</td>
              <td style="color: var(--sp-muted); font-weight:700;">
                {{ $m->note ?? '-' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="color: var(--sp-muted);">Sem movimentações.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $movements->links() }}
  </div>

</div>

{{-- MODAL --}}
<div class="modal fade" id="movModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content sp-modal" method="POST" action="{{ route('movements.store') }}">
      @csrf

      <div class="modal-header sp-modal-header">
        <div>
          <h5 class="modal-title mb-0">Registrar Movimentação</h5>
          <small class="sp-modal-sub">
            Preencha os dados abaixo para registrar
          </small>
        </div>
        <button type="button" class="btn-close sp-modal-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body sp-modal-body">

        {{-- TIPO (CHIPS) --}}
        <div class="mb-3">
          <label class="form-label sp-modal-label">Tipo de Movimentação</label>

          {{-- select escondido (é o que vai pro backend) --}}
          <select id="movType" name="type" class="form-select d-none" required>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
            <option value="ajuste">Ajuste</option>
            <option value="transferencia">Transferência</option>
            <option value="devolucao">Devolução</option>
          </select>

          <div class="mov-type-chips">
            <button type="button" class="mov-chip-btn" data-value="entrada">
              <span class="mov-chip-ic">📥</span> Entrada
            </button>
            <button type="button" class="mov-chip-btn" data-value="saida">
              <span class="mov-chip-ic">📤</span> Saída
            </button>
            <button type="button" class="mov-chip-btn" data-value="ajuste">
              <span class="mov-chip-ic">🛠️</span> Ajuste
            </button>
            <button type="button" class="mov-chip-btn" data-value="transferencia">
              <span class="mov-chip-ic">🔁</span> Transferência
            </button>
            <button type="button" class="mov-chip-btn" data-value="devolucao">
              <span class="mov-chip-ic">↩️</span> Devolução
            </button>
          </div>
        </div>

        {{-- PRODUTO --}}
        <div class="mb-3">
          <label class="form-label sp-modal-label">Produto</label>
          <select name="product_id" class="form-select sp-modal-select" required>
            <option value="" selected disabled>Selecione um produto...</option>
            @foreach($products as $p)
              <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- QTD / VALOR --}}
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label sp-modal-label">Quantidade</label>
            <input name="quantity" type="number" min="1" class="form-control sp-modal-input" required value="1">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label sp-modal-label">Valor unitário (R$)</label>
            <input name="unit_price" type="number" step="0.01" min="0" class="form-control sp-modal-input" placeholder="0,00">
          </div>
        </div>

        {{-- OBS --}}
        <div class="mt-3">
          <label class="form-label sp-modal-label">Observação</label>
          <input name="note" class="form-control sp-modal-input" maxlength="255"
                 placeholder="Ex: Compra NF 12345, venda cliente X...">
        </div>

      </div>

      <div class="modal-footer sp-modal-footer">
        <button class="sp-modal-btn sp-modal-btn-ghost" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="sp-modal-btn sp-modal-btn-primary" type="submit">💾 Salvar</button>
      </div>
    </form>
  </div>
</div>

{{-- SCRIPT: abre modal setando tipo + controla chips --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('movModal');
  const selectType = document.getElementById('movType');
  const chipBtns = Array.from(document.querySelectorAll('.mov-chip-btn'));

  function setType(type){
    if (!type) return;
    selectType.value = type;

    chipBtns.forEach(b => {
      const active = b.dataset.value === type;
      b.classList.toggle('is-active', active);

      // classes de cor por tipo (pra borda/efeito)
      b.classList.toggle('is-entrada', active && type === 'entrada');
      b.classList.toggle('is-saida', active && type === 'saida');
      b.classList.toggle('is-ajuste', active && type === 'ajuste');
      b.classList.toggle('is-transferencia', active && type === 'transferencia');
      b.classList.toggle('is-devolucao', active && type === 'devolucao');
    });
  }

  modal?.addEventListener('show.bs.modal', (event) => {
    const btn = event.relatedTarget;
    const type = btn?.getAttribute('data-mov-type') || 'entrada';
    setType(type);
  });

  chipBtns.forEach(btn => {
    btn.addEventListener('click', () => setType(btn.dataset.value));
  });

  selectType?.addEventListener('change', () => setType(selectType.value));
});
</script>
@endsection
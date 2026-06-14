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
      <a href="{{ route('movements.export', request()->only(['type','product_id','q','date_from','date_to'])) }}" class="btn btn-outline-light btn-sm">📥 Exportar CSV</a>

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
      <div class="sp-chip green">
        {{ ($percentualEntradas ?? 0) >= 0 ? '+' : '' }}{{ round($percentualEntradas ?? 0) }}%
      </div>
    </div>

    <div class="sp-value">{{ $produtosEntraram ?? 0 }}</div>
    <div class="sp-meta">
      +{{ $entradasMes ?? 0 }} unidades
    </div>
  </div>

  <div class="sp-card red">
    <div class="sp-card-top">
      <div class="sp-card-title">Saídas (mês)</div>
      <div class="sp-chip red">
        {{ ($percentualSaidas ?? 0) >= 0 ? '+' : '' }}{{ round($percentualSaidas ?? 0) }}%
      </div>
    </div>

    <div class="sp-value">{{ $produtosSairam ?? 0 }}</div>
    <div class="sp-meta">
      -{{ $saidasMes ?? 0 }} unidades
    </div>
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

    <span class="sp-label">Período</span>
    <input type="date" name="date_from" value="{{ request('date_from') }}" class="sp-select sp-date" title="Data inicial">
    <span class="sp-label">até</span>
    <input type="date" name="date_to" value="{{ request('date_to') }}" class="sp-select sp-date" title="Data final">

    <span class="sp-label">Produto</span>
    <select name="product_id" class="sp-select js-search">
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
           placeholder="Buscar por nome ou código...">

    <button class="sp-btn sp-btn-blue" type="submit">Filtrar</button>
    <a class="sp-btn" href="{{ route('movements.index') }}">Limpar filtros</a>
  </form>

  <style>
    /* faz o seletor de data nativo aparecer no tema escuro */
    .sp-date{  }
  </style>

  <div class="sp-panel">
    <div class="sp-panel-head">
      <h3 class="sp-panel-title">Histórico de Movimentações</h3>
      <div class="sp-panel-sub">Registros mais recentes</div>
    </div>

    <div class="table-responsive">
      <table class="sp-table" data-mob="cards">
        <thead>
          <tr>
            <th>Data / Hora</th>
            <th>Tipo</th>
            <th>Produto</th>
            <th>Qtd</th>
            <th>Valor Unit.</th>
            <th>Lote</th>
            <th>Responsável</th>
            <th>Obs.</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movements as $m)
            <tr @if($m->reversed_at) class="mov-reversed" @endif>
              <td data-label="Data">{{ $m->created_at->format('d/m/Y H:i') }}</td>
              <td data-label="Tipo">
                {{ ucfirst($m->type) }}
                @if($m->type === 'devolucao' && $m->direction)
                  <small style="color:var(--t-muted);">({{ $m->direction === 'fornecedor' ? 'ao fornecedor' : 'de cliente' }})</small>
                @endif
              </td>
              <td data-label="Produto">{{ $m->product->name ?? '-' }}</td>
              <td data-label="Qtd">{{ $m->quantity }}</td>
              <td data-label="Valor unit.">{{ $m->unit_price ? 'R$ '.number_format($m->unit_price,2,',','.') : '-' }}</td>
              <td data-label="Lote">{{ $m->lote ?: '—' }}</td>
              <td data-label="Responsável">{{ optional($m->creator)->name ?? optional($m->user)->name ?? '—' }}</td>
              <td data-label="Obs." style="color: var(--t-muted); font-weight:700;">
                {{ $m->note ?? '-' }}
              </td>
              <td class="mov-actions-cell" data-label="Ações">
                @if(in_array($m->type, ['devolucao', 'transferencia']))
                  <a href="{{ route('movements.document', $m) }}" target="_blank" class="mov-act mov-act-doc">📄 Documento</a>
                @endif

                @if($m->isPendingApproval())
                  @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('movements.approve', $m) }}" style="display:inline;">@csrf
                      <button type="submit" class="mov-act" style="color:#22c55e;">✓ Aprovar</button>
                    </form>
                    <form method="POST" action="{{ route('movements.reject', $m) }}" style="display:inline;" onsubmit="return confirm('Rejeitar esta movimentação?')">@csrf
                      <button type="submit" class="mov-act" style="color:#ef4444;">✕ Rejeitar</button>
                    </form>
                  @else
                    <span class="mov-badge-pend">🕓 Aguardando aprovação</span>
                  @endif
                @elseif($m->isRejected())
                  <span class="mov-badge-rej">✕ Rejeitado</span>
                @elseif($m->reversed_at)
                  <span class="mov-badge-rev">Estornado</span>
                @elseif($m->type !== 'ajuste')
                  <button type="button" class="mov-act mov-act-rev js-reverse"
                          data-url="{{ route('movements.reverse', $m) }}"
                          data-info="{{ ucfirst($m->type) }} de {{ $m->quantity }} un. — {{ $m->product->name ?? '' }}">↩ Estornar</button>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="color: var(--t-muted);">Sem movimentações.</td>
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
            <option value="entrada" {{ old('type')=='entrada' ? 'selected' : '' }}>Entrada</option>
            <option value="saida" {{ old('type')=='saida' ? 'selected' : '' }}>Saída</option>
            <option value="ajuste" {{ old('type')=='ajuste' ? 'selected' : '' }}>Ajuste</option>
            <option value="transferencia" {{ old('type')=='transferencia' ? 'selected' : '' }}>Transferência</option>
            <option value="devolucao" {{ old('type')=='devolucao' ? 'selected' : '' }}>Devolução</option>
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
          <select id="movProduct" name="product_id" class="form-select sp-modal-select js-search" required>
            <option value="" disabled {{ old('product_id') ? '' : 'selected' }}>Selecione um produto...</option>
            @foreach($products as $p)
              <option value="{{ $p->id }}"
                      data-stock="{{ $p->quantity }}"
                      data-code="{{ $p->code }}"
                      data-category="{{ $p->category }}"
                      data-supplier="{{ $p->supplier }}"
                      data-location="{{ $p->location }}"
                      {{ old('product_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }}{{ $p->code ? ' ('.$p->code.')' : '' }} — {{ $p->quantity }} em estoque
              </option>
            @endforeach
          </select>
        </div>

        {{-- INFO DO PRODUTO SELECIONADO --}}
        <div id="movProductInfo" class="mov-prod-info" style="display:none;">
          <div><span>Categoria</span><strong id="mpiCategory">—</strong></div>
          <div><span>Fornecedor</span><strong id="mpiSupplier">—</strong></div>
          <div><span>Localização</span><strong id="mpiLocation">—</strong></div>
          <div><span>Em estoque</span><strong id="mpiStock">—</strong></div>
        </div>

        {{-- QTD / VALOR / CAMPOS POR TIPO --}}
        <div class="row g-3">
          {{-- Quantidade (entrada / saida / transferencia / devolucao) --}}
          <div class="col-12 col-md-6" id="fieldQty">
            <label class="form-label sp-modal-label">Quantidade</label>
            <input id="movQty" name="quantity" type="number" min="1" class="form-control sp-modal-input" value="{{ old('quantity', 1) }}">
          </div>

          {{-- Quantidade real contada (ajuste = acerto de inventário) --}}
          <div class="col-12 col-md-6" id="fieldReal" style="display:none;">
            <label class="form-label sp-modal-label">Quantidade real contada</label>
            <input id="movReal" name="real_quantity" type="number" min="0" class="form-control sp-modal-input" value="{{ old('real_quantity') }}">
            <small id="movRealHint" class="mov-hint">Estoque atual no sistema: —</small>
          </div>

          {{-- Valor unitário (escondido no ajuste) --}}
          <div class="col-12 col-md-6" id="fieldValor">
            <label class="form-label sp-modal-label">Valor unitário (R$)</label>
            <input name="unit_price" type="number" step="0.01" min="0" class="form-control sp-modal-input" placeholder="0,00" value="{{ old('unit_price') }}">
          </div>
        </div>

        {{-- DESTINO (somente transferência) --}}
        <div class="mt-3" id="fieldDestino" style="display:none;">
          <label class="form-label sp-modal-label">Destino (loja / filial)</label>
          <input id="movDestino" name="destination" class="form-control sp-modal-input" maxlength="200" value="{{ old('destination') }}" placeholder="Ex: Loja Centro, Filial 2...">
        </div>

        {{-- DEVOLUÇÃO (somente devolução) --}}
        <div id="fieldDevol" style="display:none;">
          <div class="row g-3 mt-0">
            <div class="col-12 col-md-6">
              <label class="form-label sp-modal-label">Sentido da devolução</label>
              <select id="movDirection" name="direction" class="form-select sp-modal-select">
                <option value="fornecedor" {{ old('direction')=='fornecedor' ? 'selected' : '' }}>Ao fornecedor (sai do estoque)</option>
                <option value="cliente" {{ old('direction')=='cliente' ? 'selected' : '' }}>De cliente (volta ao estoque)</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label sp-modal-label">Data de entrada do produto</label>
              <input id="movRefDate" name="ref_date" type="date" class="form-control sp-modal-input" value="{{ old('ref_date') }}">
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label sp-modal-label">Motivo da devolução</label>
            <textarea id="movReason" name="reason" rows="2" class="form-control sp-modal-input" maxlength="500" placeholder="Ex: produto veio danificado">{{ old('reason') }}</textarea>
          </div>
        </div>

        {{-- LOTE (todos os tipos) / VALIDADE (entrada e devolução) --}}
        <div id="fieldLote" style="display:none;">
          <hr class="np-sep" style="border-color:var(--t-border);">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label sp-modal-label">Lote (opcional)</label>
              <input id="movBatchLote" name="batch_lote" class="form-control sp-modal-input" maxlength="80" value="{{ old('batch_lote') }}" placeholder="Ex: L2026-04">
            </div>
            <div class="col-12 col-md-6" id="fieldValidade">
              <label class="form-label sp-modal-label">Validade</label>
              <input id="movBatchExpiry" name="batch_expiry" type="date" class="form-control sp-modal-input" value="{{ old('batch_expiry') }}">
            </div>
          </div>
          <small class="mov-hint" id="loteHintIn">Informe a validade para registrar este estoque como lote — aparece em Validades.</small>
          <small class="mov-hint" id="loteHintOut" style="display:none;">Deixe vazio para baixa automática (vence primeiro). Ou informe um lote específico para baixar dele.</small>
          <small class="mov-hint" id="loteHintAdj" style="display:none;">Apenas informativo — o acerto de inventário não altera lotes.</small>
        </div>

        {{-- AVISO DE ESTOQUE --}}
        <div id="movStockWarn" class="mov-stock-warn" style="display:none;"></div>

        {{-- OBS --}}
        <div class="mt-3">
          <label class="form-label sp-modal-label">Observação</label>
          <input name="note" class="form-control sp-modal-input" maxlength="255" value="{{ old('note') }}"
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
<style>
  .mov-stock-warn{ margin-top:10px; padding:9px 12px; border-radius:9px; background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.45); color:#ef4444; font-size:.85rem; }
  .sp-modal-input.is-invalid{ border-color:#ef4444 !important; box-shadow:none !important; }
  .mov-prod-info{ margin-top:12px; display:grid; grid-template-columns:1fr 1fr; gap:10px 16px; background:var(--t-panel-2); border:1px solid var(--t-border); border-radius:10px; padding:12px 14px; }
  .mov-prod-info > div{ display:flex; flex-direction:column; gap:2px; }
  .mov-prod-info span{ font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--t-muted); }
  .mov-prod-info strong{ font-size:.9rem; color:var(--t-text); }
  .mov-hint{ display:block; margin-top:6px; color:var(--t-muted); font-size:.78rem; }
  .mov-actions-cell{ white-space:nowrap; }
  .mov-act{ display:inline-flex; align-items:center; gap:4px; padding:4px 9px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; text-decoration:none; border:1px solid var(--t-input-border); background:var(--t-panel-2); color:var(--t-text); margin-right:4px; }
  .mov-act-rev:hover{ border-color:#ffb700; color:#ffb700; }
  .mov-act-doc:hover{ border-color:#3b82f6; color:#3b82f6; }
  .mov-badge-rev{ display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:rgba(255,183,0,.15); color:#ffb700; }
  .mov-badge-pend{ display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:rgba(255,183,0,.15); color:#ffb700; }
  .mov-badge-rej{ display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:rgba(239,68,68,.15); color:#ef4444; }
  .mov-reversed td{ opacity:.5; text-decoration:line-through; }
  .mov-reversed .mov-badge-rev{ text-decoration:none; opacity:1; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('movModal');
  const selectType = document.getElementById('movType');
  const chipBtns = Array.from(document.querySelectorAll('.mov-chip-btn'));
  const selProduct = document.getElementById('movProduct');
  const inpQty = document.getElementById('movQty');
  const warn = document.getElementById('movStockWarn');
  const submitBtn = document.querySelector('#movModal button[type="submit"]');

  // Campos por tipo
  const fieldQty     = document.getElementById('fieldQty');
  const fieldReal    = document.getElementById('fieldReal');
  const fieldValor   = document.getElementById('fieldValor');
  const fieldDestino = document.getElementById('fieldDestino');
  const fieldDevol   = document.getElementById('fieldDevol');
  const fieldLote     = document.getElementById('fieldLote');
  const fieldValidade = document.getElementById('fieldValidade');
  const loteHintIn    = document.getElementById('loteHintIn');
  const loteHintOut   = document.getElementById('loteHintOut');
  const loteHintAdj   = document.getElementById('loteHintAdj');
  const movReal      = document.getElementById('movReal');
  const movDestino   = document.getElementById('movDestino');
  const movDirection = document.getElementById('movDirection');
  const movReason    = document.getElementById('movReason');
  const movRealHint  = document.getElementById('movRealHint');

  function _show(el, on){ if (el) el.style.display = on ? '' : 'none'; }
  function _req(el, on){ if (el){ if (on) el.setAttribute('required','required'); else el.removeAttribute('required'); } }

  function updateRealHint(){
    const opt = selProduct && selProduct.selectedOptions[0];
    const stock = (opt && opt.dataset.stock !== undefined) ? opt.dataset.stock : '—';
    if (movRealHint) movRealHint.textContent = 'Estoque atual no sistema: ' + stock;
  }

  function applyTypeUI(type){
    _show(fieldQty, true);     _req(inpQty, true);
    _show(fieldReal, false);   _req(movReal, false);
    _show(fieldValor, true);
    _show(fieldDestino, false);_req(movDestino, false);
    _show(fieldDevol, false);  _req(movDirection, false); _req(movReason, false);
    _show(fieldLote, true);
    var isIn = (type === 'entrada' || type === 'devolucao');
    _show(fieldValidade, isIn);
    _show(loteHintIn, isIn);
    _show(loteHintOut, type === 'saida' || type === 'transferencia');
    _show(loteHintAdj, type === 'ajuste');

    if (type === 'ajuste'){
      _show(fieldQty, false);  _req(inpQty, false);
      _show(fieldValor, false);
      _show(fieldReal, true);  _req(movReal, true);
      updateRealHint();
    } else if (type === 'transferencia'){
      _show(fieldDestino, true); _req(movDestino, true);
    } else if (type === 'devolucao'){
      _show(fieldDevol, true);   _req(movDirection, true); _req(movReason, true);
    }
  }

  function currentStock(){
    const opt = selProduct && selProduct.selectedOptions[0];
    if (!opt || opt.dataset.stock === undefined) return null;
    return parseInt(opt.dataset.stock, 10);
  }

  const movInfo = document.getElementById('movProductInfo');
  function fillProductInfo(){
    const opt = selProduct && selProduct.selectedOptions[0];
    if (!opt || !opt.value){ if (movInfo) movInfo.style.display = 'none'; return; }
    const v = function(x){ return (x && x.trim()) ? x : '—'; };
    const setTxt = function(id, val){ var el = document.getElementById(id); if (el) el.textContent = val; };
    setTxt('mpiCategory', v(opt.dataset.category));
    setTxt('mpiSupplier', v(opt.dataset.supplier));
    setTxt('mpiLocation', v(opt.dataset.location));
    setTxt('mpiStock', (opt.dataset.stock || '0') + ' un.');
    if (movInfo) movInfo.style.display = 'grid';
  }

  // Aviso instantaneo: bloqueia saida/transferencia maior que o estoque
  function checkStock(){
    const type = selectType.value;
    const isOut = (type === 'saida' || type === 'transferencia');
    const stock = currentStock();
    const qty = parseInt(inpQty && inpQty.value, 10) || 0;
    let block = false;

    if (isOut && stock !== null && qty > stock){
      block = true;
      if (warn){
        warn.style.display = 'block';
        warn.textContent = 'Estoque insuficiente: ha apenas ' + stock + ' unidade(s) disponivel(is). Reduza a quantidade.';
      }
      inpQty && inpQty.classList.add('is-invalid');
    } else {
      if (warn) warn.style.display = 'none';
      inpQty && inpQty.classList.remove('is-invalid');
    }
    if (submitBtn){
      submitBtn.disabled = block;
      submitBtn.style.opacity = block ? '.5' : '1';
      submitBtn.style.cursor = block ? 'not-allowed' : 'pointer';
    }
  }

  function setType(type){
    if (!type) return;
    selectType.value = type;
    chipBtns.forEach(b => {
      const active = b.dataset.value === type;
      b.classList.toggle('is-active', active);
      b.classList.toggle('is-entrada', active && type === 'entrada');
      b.classList.toggle('is-saida', active && type === 'saida');
      b.classList.toggle('is-ajuste', active && type === 'ajuste');
      b.classList.toggle('is-transferencia', active && type === 'transferencia');
      b.classList.toggle('is-devolucao', active && type === 'devolucao');
    });
    applyTypeUI(type);
    checkStock();
  }

  modal?.addEventListener('show.bs.modal', (event) => {
    const btn = event.relatedTarget;
    if (!btn) return; // reabertura programatica nao sobrescreve o tipo
    const type = btn.getAttribute('data-mov-type') || 'entrada';
    setType(type);
  });

  chipBtns.forEach(btn => btn.addEventListener('click', () => setType(btn.dataset.value)));
  selectType?.addEventListener('change', () => setType(selectType.value));
  selProduct?.addEventListener('change', checkStock);
  selProduct?.addEventListener('change', fillProductInfo);
  selProduct?.addEventListener('change', updateRealHint);
  fillProductInfo();
  inpQty?.addEventListener('input', checkStock);

  applyTypeUI((selectType && selectType.value) || 'entrada');

  // Se o servidor recusou (estoque insuficiente), reabre o modal com os dados
  @if($errors->any() && old('type'))
    setType(@json(old('type')));
    checkStock();
    if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
  @endif
});
</script>

<script>
(function(){
  var input = document.querySelector('input[name="q"]');
  var tbody = document.querySelector('.sp-table tbody');
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

{{-- ===== MODAL: confirmar estorno ===== --}}
<div class="rev-overlay" id="revOverlay">
  <div class="rev-box">
    <div class="rev-ico">↩</div>
    <h5 class="rev-title">Estornar movimentação?</h5>
    <p class="rev-text">Isto cria um lançamento oposto e <strong>restaura o estoque</strong>. O registro original fica marcado como estornado (não é apagado), mantendo o histórico.</p>
    <p class="rev-info" id="revInfo"></p>
    <div class="rev-actions">
      <button type="button" class="btn btn-secondary" id="revCancel">Cancelar</button>
      <form method="POST" id="revForm" action="#" style="margin:0;">
        @csrf
        <button type="submit" class="btn rev-confirm">↩ Sim, estornar</button>
      </form>
    </div>
  </div>
</div>

<style>
  .rev-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.55); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:2000; }
  .rev-overlay.open{ display:flex; }
  .rev-box{ background:var(--t-panel-2); border:1px solid rgba(124,92,252,.45); border-radius:18px; padding:28px 26px; max-width:440px; width:92%; text-align:center; color:var(--sp-text,#e5e7eb); box-shadow:0 30px 60px rgba(0,0,0,.5); }
  .rev-ico{ font-size:38px; margin-bottom:8px; color:#a78bfa; }
  .rev-title{ font-weight:800; margin:0 0 8px; }
  .rev-text{ color:var(--t-muted); font-size:.9rem; margin:0 0 8px; line-height:1.5; }
  .rev-info{ font-size:.85rem; color:#a78bfa; margin:0 0 20px; font-weight:600; }
  .rev-actions{ display:flex; gap:10px; justify-content:center; }
  .rev-actions form{ margin:0; }
  .rev-confirm{ background:rgba(124,92,252,.18); color:#a78bfa; border:1px solid rgba(124,92,252,.5); font-weight:600; }
  .rev-confirm:hover{ background:rgba(124,92,252,.3); color:#a78bfa; }
</style>

<script>
(function(){
  var overlay = document.getElementById('revOverlay');
  var form = document.getElementById('revForm');
  var info = document.getElementById('revInfo');
  var cancel = document.getElementById('revCancel');
  function closeRev(){ if (overlay) overlay.classList.remove('open'); }
  if (cancel) cancel.addEventListener('click', closeRev);
  if (overlay) overlay.addEventListener('click', function(e){ if (e.target === overlay) closeRev(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeRev(); });
  document.querySelectorAll('.js-reverse').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (form) form.action = btn.dataset.url;
      if (info) info.textContent = btn.dataset.info || '';
      if (overlay) overlay.classList.add('open');
    });
  });
})();
</script>
@endsection
@extends('layouts.app')

@section('title', 'Validades')

@php
    $statusMap = [
        'vencido' => ['Vencido',         'vd-bad'],
        'd7'      => ['Vence em 7 dias',  'vd-bad'],
        'd30'     => ['Vence em 30 dias', 'vd-warn'],
        'd90'     => ['Vence em 90 dias', 'vd-soon'],
        'ok'      => ['No prazo',         'vd-ok'],
    ];
@endphp

@section('content')
<div class="reports-dark">

    {{-- Cabeçalho --}}
    <div class="rp-header">
        <div>
            <h3 class="rp-title">Validades</h3>
            <p class="rp-sub">Controle de lotes e produtos próximos do vencimento</p>
        </div>
        <div class="rp-actions">
            <button type="button" class="btn btn-primary btn-sm" id="btnNovoLote">＋ Novo lote</button>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    {{-- Cadastro de lote (escondido por padrão) --}}
    <div class="rp-panel vd-form {{ $errors->any() ? '' : 'vd-hidden' }}" id="formLote">
        <div class="rp-panel__title">＋ Cadastrar lote</div>
        <form method="POST" action="{{ route('batches.store') }}" class="vd-grid">
            @csrf
            <div class="vd-field vd-col-2">
                <label>Produto *</label>
                <select name="product_id" class="rp-sel js-search" required>
                    <option value="">Selecione...</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}{{ $p->code ? ' ('.$p->code.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="vd-field">
                <label>Lote</label>
                <input type="text" name="lote" id="loteInput" class="rp-input vd-plain" placeholder="Ex.: L2026-04" value="{{ old('lote') }}" autocomplete="off">
                <div id="loteSugg" class="vd-lote-sugg"></div>
            </div>
            <div class="vd-field">
                <label>Validade *</label>
                <input type="date" name="expiry_date" class="rp-input vd-plain" value="{{ old('expiry_date') }}" required>
            </div>
            <div class="vd-field">
                <label>Quantidade *</label>
                <input type="number" name="quantity" class="rp-input vd-plain" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="vd-field">
                <label>Data de entrada</label>
                <input type="date" name="entry_date" class="rp-input vd-plain" value="{{ old('entry_date') }}">
            </div>
            <div class="vd-field vd-actions">
                <button type="submit" class="btn btn-primary btn-sm">Salvar lote</button>
            </div>
        </form>
        @if ($errors->any())
            <div class="vd-error">{{ $errors->first() }}</div>
        @endif
    </div>

    {{-- KPIs --}}
    <div class="rp-kpis">
        <div class="rp-card rp-card--red">
            <div class="rp-card__label">✕ Vencidos</div>
            <div class="rp-card__value">{{ $kpis['vencidos'] }}</div>
            <div class="rp-card__foot">lotes com estoque</div>
        </div>
        <div class="rp-card rp-card--red">
            <div class="rp-card__label">⏰ Vencem em 7 dias</div>
            <div class="rp-card__value">{{ $kpis['d7'] }}</div>
            <div class="rp-card__foot">ação urgente</div>
        </div>
        <div class="rp-card rp-card--yellow">
            <div class="rp-card__label">⚠ Vencem em 30 dias</div>
            <div class="rp-card__value">{{ $kpis['d30'] }}</div>
            <div class="rp-card__foot">atenção</div>
        </div>
        <div class="rp-card">
            <div class="rp-card__label">📅 Vencem em 90 dias</div>
            <div class="rp-card__value">{{ $kpis['d90'] }}</div>
            <div class="rp-card__foot">monitorar</div>
        </div>
    </div>

    {{-- Filtros (abas) --}}
    <div class="rp-tabs">
        @php
            $tabs = [
                'all'      => ['📋 Todos',     $kpis['total'],    ''],
                'vencidos' => ['✕ Vencidos',   $kpis['vencidos'], 'rp-tab--red'],
                'd7'       => ['⏰ 7 dias',     $kpis['d7'],       'rp-tab--red'],
                'd30'      => ['⚠ 30 dias',    $kpis['d30'],      'rp-tab--yellow'],
                'd90'      => ['📅 90 dias',    $kpis['d90'],      ''],
            ];
        @endphp
        @foreach ($tabs as $key => $t)
            <a href="{{ request()->fullUrlWithQuery(['filter' => $key, 'page' => null]) }}"
               class="rp-tab {{ $t[2] }} {{ $filter === $key ? 'active' : '' }}">
                {{ $t[0] }} <span class="cnt">{{ $t[1] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Tabela --}}
    <div class="rp-report">
        <div class="table-responsive">
            <table class="rp-table" data-mob="cards">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Situação</th>
                        <th class="vd-r">Quantidade</th>
                        <th class="vd-r">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $b)
                        @php [$label, $cls] = $statusMap[$b->status]; @endphp
                        <tr>
                            <td data-label="Produto"><span class="rp-pname">{{ $b->product->name ?? '—' }}</span></td>
                            <td data-label="Lote">{{ $b->lote ?? '—' }}</td>
                            <td data-label="Validade">{{ $b->expiry_date->format('d/m/Y') }}</td>
                            <td data-label="Situação">
                                <span class="vd-badge {{ $cls }}">{{ $label }}</span>
                                @if ($b->days_left >= 0)
                                    <span class="vd-days">({{ $b->days_left }}d)</span>
                                @else
                                    <span class="vd-days">(há {{ abs($b->days_left) }}d)</span>
                                @endif
                            </td>
                            <td data-label="Quantidade" class="vd-r"><strong>{{ $b->quantity }}</strong></td>
                            <td data-label="Ações" class="vd-r">
                                <button type="button" class="rp-icon-btn js-edit-lote"
                                        data-url="{{ route('batches.update', $b) }}"
                                        data-name="{{ $b->product->name ?? 'este produto' }}"
                                        data-lote="{{ e($b->lote) }}"
                                        data-expiry="{{ optional($b->expiry_date)->format('Y-m-d') }}"
                                        data-quantity="{{ $b->quantity }}"
                                        data-entry="{{ optional($b->entry_date)->format('Y-m-d') }}" title="Editar">✏️</button>
                                <button type="button" class="rp-icon-btn js-del-lote"
                                        data-url="{{ route('batches.destroy', $b) }}"
                                        data-name="{{ $b->product->name ?? 'este lote' }}" title="Remover">🗑</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="vd-empty">Nenhum lote neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($batches->hasPages())
            <div class="rp-pagination">
                <span class="rp-pag-info">{{ $batches->firstItem() }}–{{ $batches->lastItem() }} de {{ $batches->total() }}</span>
                {{ $batches->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- ===== MODAL: Excluir lote (confirmação) ===== --}}
<div class="modal fade" id="deleteLoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content del-modal">
      <div class="del-ico">🗑️</div>
      <h5 class="del-title">Excluir lote?</h5>
      <p class="del-text">Tem certeza que deseja excluir o lote de &ldquo;<span id="delLoteName">este produto</span>&rdquo;?<br>Esta ação não pode ser desfeita.</p>
      <div class="del-actions">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form method="POST" id="delLoteForm" action="#" style="margin:0;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn del-confirm">Sim, excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ===== MODAL: Editar lote ===== --}}
<div class="modal fade" id="editLoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content vd-edit-modal">
      <div class="vd-edit-head">
        <h5 style="margin:0;font-weight:800;">✏️ Editar lote</h5>
        <button type="button" class="vd-x" data-bs-dismiss="modal" aria-label="Fechar">✕</button>
      </div>
      <p class="vd-edit-prod">Produto: <strong id="editLoteProd">—</strong></p>
      <form method="POST" id="editLoteForm" action="#">
        @csrf
        @method('PUT')
        <div class="vd-edit-grid">
          <div class="vd-field"><label>Lote</label><input type="text" name="lote" id="editLoteLote" class="rp-input vd-plain" maxlength="80" placeholder="Ex.: L2026-04"></div>
          <div class="vd-field"><label>Validade *</label><input type="date" name="expiry_date" id="editLoteExpiry" class="rp-input vd-plain" required></div>
          <div class="vd-field"><label>Quantidade *</label><input type="number" name="quantity" id="editLoteQty" class="rp-input vd-plain" min="0" required></div>
          <div class="vd-field"><label>Data de entrada</label><input type="date" name="entry_date" id="editLoteEntry" class="rp-input vd-plain"></div>
        </div>
        <div class="del-actions" style="margin-top:18px;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
    /* ===== Vocabulário do app (mesmo da tela de Relatórios) ===== */
    .reports-dark{ color: var(--text, #e5e7eb); }
    .rp-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .rp-title{ margin:0; font-weight:800; }
    .rp-sub{ color: var(--muted, #94a3b8); margin:4px 0 0; font-size:.9rem; }
    .rp-actions{ display:flex; gap:8px; flex-wrap:wrap; }

    .rp-kpis{ display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-bottom:16px; }
    .rp-card{ background: var(--panel, #111827); border:1px solid var(--border, rgba(255,255,255,.08)); border-radius:16px; padding:18px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; } .rp-card--red::before{ background:#ef4444; }
    .rp-card__label{ color: var(--muted, #94a3b8); font-size:.82rem; }
    .rp-card__value{ font-size:1.9rem; font-weight:800; margin:6px 0 2px; }
    .rp-card__foot{ color: var(--muted, #94a3b8); font-size:.78rem; }

    .rp-panel{ background: var(--panel, #111827); border:1px solid var(--border, rgba(255,255,255,.08)); border-radius:16px; padding:18px; margin-bottom:16px; }
    .rp-panel__title{ font-weight:700; margin-bottom:14px; }

    .rp-tabs{ display:flex; gap:4px; flex-wrap:wrap; }
    .rp-tab{ padding:10px 16px; color: var(--muted, #94a3b8); text-decoration:none; border-bottom:2px solid transparent; font-size:.9rem; }
    .rp-tab .cnt{ display:inline-block; margin-left:6px; padding:1px 9px; border-radius:999px; background: rgba(255,255,255,.08); font-size:.75rem; font-weight:700; }
    .rp-tab.active{ color: var(--accent, #3b82f6); border-bottom-color: var(--accent, #3b82f6); font-weight:600; }
    .rp-tab.active .cnt{ background: rgba(59,130,246,.25); color:#fff; }
    .rp-tab--yellow.active{ color:#ffb700; border-bottom-color:#ffb700; } .rp-tab--yellow.active .cnt{ background:rgba(245,200,66,.25); }
    .rp-tab--red.active{ color:#ef4444; border-bottom-color:#ef4444; } .rp-tab--red.active .cnt{ background:rgba(239,68,68,.25); }

    .rp-report{ background: var(--panel, #111827); border:1px solid var(--border, rgba(255,255,255,.08)); border-radius:0 12px 12px 12px; overflow:hidden; }

    .rp-input{ width:100%; height:40px; padding:0 12px; border-radius:10px; background: var(--panel-2, #0b1220); border:1px solid var(--border, rgba(255,255,255,.14)); color: var(--text, #e5e7eb); outline:none; color-scheme: dark; }
    .rp-input::placeholder{ color: var(--muted, #94a3b8); }
    .rp-input:focus{ border-color: var(--accent, #3b82f6); }
    .rp-sel{ width:100%; height:40px; padding:0 12px; border-radius:10px; background: var(--panel-2, #0b1220); border:1px solid var(--border, rgba(255,255,255,.14)); color: var(--text, #e5e7eb); color-scheme: dark; cursor:pointer; }
    .rp-icon-btn{ width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background: var(--panel-2, #0b1220); border:1px solid var(--border, rgba(255,255,255,.14)); cursor:pointer; font-size:15px; }
    .rp-icon-btn:hover{ border-color:#ef4444; background: rgba(239,68,68,.12); }
    .js-edit-lote:hover{ border-color: var(--accent,#3b82f6) !important; background: rgba(59,130,246,.14) !important; }
    .vd-edit-modal{ background:var(--panel,#111827); border:1px solid var(--border,rgba(255,255,255,.12)); border-radius:18px; padding:24px; color:var(--text,#e5e7eb); }
    .vd-edit-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
    .vd-x{ background:transparent; border:0; color:var(--muted,#94a3b8); font-size:16px; cursor:pointer; }
    .vd-edit-prod{ color:var(--muted,#94a3b8); font-size:.9rem; margin:2px 0 16px; }
    .vd-edit-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .vd-edit-grid .vd-field{ display:flex; flex-direction:column; gap:6px; }
    .vd-edit-grid label{ font-size:.72rem; color:var(--muted,#94a3b8); text-transform:uppercase; letter-spacing:.04em; }
    #editLoteModal{ z-index:1260 !important; }
    @media (max-width:560px){ .vd-edit-grid{ grid-template-columns:1fr; } }

    /* Tabela escura (vence Bootstrap + dashboard-pro.css) */
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--text, #e5e7eb) !important; box-shadow:none !important; }
    .rp-table{ width:100%; border-collapse:collapse; }
    .rp-table thead th{ color: var(--muted, #94a3b8) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:12px 16px; text-align:left; border-bottom:1px solid var(--border, rgba(255,255,255,.10)) !important; background: var(--panel-2, #0b1220) !important; white-space:nowrap; }
    .rp-table tbody td{ padding:12px 16px; border-bottom:1px solid var(--border, rgba(255,255,255,.07)) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }
    .rp-pname{ font-weight:600; }
    .vd-r{ text-align:right; }
    .vd-empty{ text-align:center; padding:26px; color: var(--muted, #94a3b8); }

    .rp-pagination{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; flex-wrap:wrap; gap:10px; }
    .rp-pag-info{ color: var(--muted, #94a3b8); font-size:.85rem; }
    .rp-pagination .pagination{ margin:0; }
    .rp-pagination .page-link{ background: var(--panel-2, #0b1220); border-color: var(--border, rgba(255,255,255,.12)); color: var(--text, #e5e7eb); }
    .rp-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .rp-pagination .page-item.disabled .page-link{ background: transparent; color: var(--muted, #94a3b8); }

    /* ===== Específico de Validades ===== */
    .vd-hidden{ display:none; }
    .vd-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; }
    .vd-col-2{ grid-column: span 2; }
    .vd-field label{ display:block; font-size:.74rem; color:var(--muted, #94a3b8); text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
    .vd-actions{ display:flex; align-items:flex-end; }
    .vd-error{ color:#fca5a5; font-size:.85rem; margin-top:10px; }

    .vd-badge{ display:inline-block; padding:3px 10px; border-radius:999px; font-size:.74rem; font-weight:700; white-space:nowrap; }
    .vd-bad { background: rgba(239,68,68,.15);  color:#fca5a5; }
    .vd-warn{ background: rgba(255,183,0,.15);  color:#fcd34d; }
    .vd-soon{ background: rgba(59,130,246,.15); color:#93c5fd; }
    .vd-ok  { background: rgba(34,197,94,.15);  color:#86efac; }
    .vd-days{ color:var(--muted, #94a3b8); font-size:.72rem; margin-left:4px; }

    /* ===== Modal de exclusão (igual ao de produtos) ===== */
    #deleteLoteModal .modal-dialog{ max-width:420px; }
    .modal-backdrop.show{ z-index:1250 !important; background: rgba(8,12,22,.6) !important; opacity:1 !important; backdrop-filter: blur(7px); -webkit-backdrop-filter: blur(7px); }
    #deleteLoteModal{ z-index:1260 !important; }
    .del-modal{ background: var(--panel, #111827); border:1px solid rgba(239,68,68,.35); border-radius:18px; padding:30px 26px; text-align:center; color: var(--text, #e5e7eb); }
    .del-ico{ font-size:42px; margin-bottom:10px; }
    .del-title{ font-weight:800; margin:0 0 8px; }
    .del-text{ color: var(--muted, #94a3b8); font-size:.9rem; margin:0 0 22px; line-height:1.5; }
    .del-actions{ display:flex; gap:10px; justify-content:center; }
    .del-actions form{ margin:0; }
    .del-confirm{ background: rgba(239,68,68,.15); color:#ef4444; border:1px solid rgba(239,68,68,.45); font-weight:600; }
    .del-confirm:hover{ background: rgba(239,68,68,.28); color:#ef4444; }

    /* ===== Responsivo ===== */
    @media (max-width: 992px){
        .rp-kpis{ grid-template-columns: repeat(2, 1fr); }
        .vd-grid{ grid-template-columns: repeat(2, 1fr); }
        .vd-col-2{ grid-column: span 2; }
    }
    @media (max-width: 600px){
        .rp-kpis{ grid-template-columns: 1fr; }
        .vd-grid{ grid-template-columns: 1fr; }
        .vd-col-2{ grid-column: span 1; }
        .rp-actions .btn{ width:100%; }
    }
</style>

<script>
    document.getElementById('btnNovoLote')?.addEventListener('click', function () {
        document.getElementById('formLote')?.classList.toggle('vd-hidden');
    });

    // Modal de confirmação de exclusão de lote
    function showModal(id){ var el=document.getElementById(id); if(el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show(); }

    document.querySelectorAll('.js-edit-lote').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.getElementById('editLoteProd').textContent = btn.dataset.name || '—';
            document.getElementById('editLoteLote').value     = btn.dataset.lote || '';
            document.getElementById('editLoteExpiry').value   = btn.dataset.expiry || '';
            document.getElementById('editLoteQty').value      = btn.dataset.quantity || '';
            document.getElementById('editLoteEntry').value    = btn.dataset.entry || '';
            var f = document.getElementById('editLoteForm'); if(f) f.action = btn.dataset.url;
            showModal('editLoteModal');
        });
    });

    document.querySelectorAll('.js-del-lote').forEach(function(btn){
        btn.addEventListener('click', function(){
            var n = document.getElementById('delLoteName'); if(n) n.textContent = btn.dataset.name || 'este produto';
            var f = document.getElementById('delLoteForm'); if(f) f.action = btn.dataset.url;
            showModal('deleteLoteModal');
        });
    });
</script>

<style>
    .vd-lote-sugg{ display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-top:6px; }
    .vd-lote-sugg__lbl{ font-size:.72rem; color:var(--muted,#94a3b8); margin-right:2px; }
    .vd-lote-chip{ font-size:.74rem; padding:3px 10px; border-radius:999px; background:rgba(124,92,252,.16); color:#c4b5fd; border:1px solid rgba(124,92,252,.4); cursor:pointer; }
    .vd-lote-chip:hover{ background:rgba(124,92,252,.32); }
</style>

<script>
  (function(){
    var LOTES = @json($lotesByProduct ?? []);
    var sel = document.querySelector('select[name="product_id"]');
    var inp = document.getElementById('loteInput');
    var box = document.getElementById('loteSugg');
    if (!sel || !inp || !box) return;

    function render(){
      var lotes = LOTES[sel.value] || [];
      box.innerHTML = '';
      if (!lotes.length) return;

      if (lotes.length === 1){
        inp.value = lotes[0];                 // só um lote -> preenche automático
      }
      var lbl = document.createElement('span');
      lbl.className = 'vd-lote-sugg__lbl';
      lbl.textContent = (lotes.length === 1) ? 'Lote deste produto:' : 'Lotes deste produto (clique para usar):';
      box.appendChild(lbl);

      lotes.forEach(function(l){
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'vd-lote-chip';
        b.textContent = l;
        b.addEventListener('click', function(){ inp.value = l; });
        box.appendChild(b);
      });
    }

    // Tom Select dispara 'change' no select nativo
    sel.addEventListener('change', render);
    if (sel.value) render();   // caso já venha selecionado (ex.: erro de validação)
  })();
</script>
@endsection

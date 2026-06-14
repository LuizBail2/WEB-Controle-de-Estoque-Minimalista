@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $palette = ['#3b82f6','#22c55e','#7c5cfc','#ffb700','#ef4444','#06b6d4','#ec4899','#f97316'];
    $catColor = function ($name) use ($categoryColors, $palette) {
        if (!$name || $name === 'Sem categoria') return '#94a3b8';
        return $categoryColors[$name] ?? $palette[abs(crc32($name)) % count($palette)];
    };
@endphp

@section('content')
<div class="reports-dark dash">

    {{-- Cabeçalho --}}
    <div class="dash-header">
        <div>
            <h3 class="dash-title">Dashboard</h3>
            <p class="dash-sub">Visão geral do seu estoque</p>
        </div>
        <div class="dash-actions">
            <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-sm">Ver Produtos</a>
        </div>
    </div>

    {{-- Cartões de topo --}}
    <div class="dash-kpis">
        <div class="rp-card rp-card--blue">
            <div class="rp-card__label">Total de Produtos</div>
            <div class="rp-card__value">{{ $stats->total }}</div>
            <div class="rp-card__foot">itens cadastrados</div>
        </div>
        <div class="rp-card rp-card--green">
            <div class="rp-card__label">Valor em Estoque</div>
            <div class="rp-card__value" style="font-size:1.5rem;">R$ {{ number_format($stats->valor, 2, ',', '.') }}</div>
            <div class="rp-card__foot">valor total do inventário</div>
        </div>
        <div class="rp-card rp-card--yellow">
            <div class="rp-card__label">Estoque Baixo</div>
            <div class="rp-card__value">{{ $stats->baixo }}</div>
            <div class="rp-card__foot">reposição recomendada</div>
        </div>
        <div class="rp-card rp-card--red">
            <div class="rp-card__label">Sem Estoque</div>
            <div class="rp-card__value">{{ $stats->sem }}</div>
            <div class="rp-card__foot">reposição urgente</div>
        </div>
    </div>

    {{-- Grade: catálogo (esquerda) + painel lateral (direita) --}}
    <div class="dash-grid">

        {{-- Catálogo --}}
        <div class="rp-report">
            <div class="dash-cat-head">
                <div>
                    <div class="dash-cat-title">Catálogo de Produtos</div>
                    <div class="dash-cat-sub">{{ $stats->total }} itens cadastrados</div>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="dash-cat-search">
                    <input type="text" name="search" value="{{ request('search') }}" class="rp-input" placeholder="🔍 Buscar produto...">
                    <select name="category" class="rp-sel" onchange="this.form.submit()">
                        <option value="">Todas categorias</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table rp-table dash-tbl align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Qtd. Estoque</th>
                            <th class="text-end">Preço Unit.</th>
                            <th class="text-end">Valor Total</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            @php
                                $isOut = $p->quantity == 0;
                                $isLow = $p->quantity > 0 && $p->quantity <= $p->minimum_quantity;
                                $c = $catColor($p->category);
                                $val = ($p->price ?? 0) * $p->quantity;
                                $ref = max($p->minimum_quantity * 2, 1);
                                $fill = min(round($p->quantity / $ref * 100), 100);
                                $barColor = $isOut ? '#ef4444' : ($isLow ? '#ffb700' : '#22c55e');
                            @endphp
                            <tr>
                                <td>
                                    <div class="rp-prod">
                                        <span class="rp-ico" style="background: {{ $c }}22; color: {{ $c }};">📦</span>
                                        <div>
                                            <div class="rp-pname">{{ $p->name }}</div>
                                            <div class="rp-psku">{{ $p->code ?: '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($p->category)
                                        <span class="rp-cat" style="background: {{ $c }}22; color: {{ $c }};">{{ $p->category }}</span>
                                    @else <span style="color:var(--t-muted)">—</span> @endif
                                </td>
                                <td>
                                    <div class="rp-qty">
                                        <span class="rp-bar"><span class="rp-bar__fill" style="width: {{ $fill }}%; background: {{ $barColor }};"></span></span>
                                        <span class="rp-qty__n">{{ $p->quantity }}</span>
                                    </div>
                                </td>
                                <td class="text-end">R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    @if($val > 0)<span class="rp-money">R$ {{ number_format($val, 2, ',', '.') }}</span>
                                    @else <span style="color:var(--t-muted)">—</span> @endif
                                </td>
                                <td>
                                    @if($isOut)<span class="dash-dot dash-dot--red"></span> Sem estoque
                                    @elseif($isLow)<span class="dash-dot dash-dot--yellow"></span> Estoque baixo
                                    @else <span class="dash-dot dash-dot--green"></span> Em estoque @endif
                                </td>
                                <td class="text-end">
                                    <div class="dash-acts">
                                        <button type="button" class="dash-eye-m"
                                                data-name="{{ e($p->name) }}"
                                                data-code="{{ e($p->code ?: '—') }}"
                                                data-category="{{ e($p->category ?: '—') }}"
                                                data-qty="{{ $p->quantity }}"
                                                data-price="R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}"
                                                data-value="{{ $val > 0 ? 'R$ '.number_format($val, 2, ',', '.') : '—' }}"
                                                data-status="{{ $isOut ? 'Sem estoque' : ($isLow ? 'Estoque baixo' : 'Em estoque') }}"
                                                title="Ver detalhes"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                        <a href="{{ route('products.index') }}" class="dash-act" title="Ver na lista"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                        <a href="{{ route('products.edit', $p) }}" class="dash-edit" title="Editar"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg> Editar</a>
                                        <form method="POST" action="{{ route('products.destroy', $p) }}" onsubmit="return confirm('Excluir “{{ $p->name }}”?')" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dash-act dash-act--del" title="Excluir"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4" style="color:var(--t-muted)">Nenhum produto encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Coluna lateral: alertas + gráficos --}}
        <div class="dash-side">

            {{-- Alertas de Estoque --}}
            <div class="rp-panel">
                <div class="rp-panel__title">⚠️ Alertas de Estoque</div>
                <div class="dash-alert-sub">Itens que precisam de atenção</div>

                @forelse($alerts as $a)
                    @php $aOut = $a->quantity == 0; @endphp
                    <div class="dash-alert">
                        <span class="dash-alert__ico" style="background: {{ $aOut ? 'rgba(239,68,68,.15)' : 'rgba(245,200,66,.15)' }};">{{ $aOut ? '✕' : '⚠️' }}</span>
                        <div class="dash-alert__info">
                            <div class="dash-alert__name">{{ $a->name }}</div>
                            <div class="dash-alert__status">{{ $aOut ? 'Sem estoque' : 'Estoque baixo' }}</div>
                        </div>
                        <div class="dash-alert__qty {{ $aOut ? 'text-danger' : '' }}">
                            <strong>{{ $a->quantity }}</strong><span>unid.</span>
                        </div>
                    </div>
                @empty
                    <div class="dash-alert-sub" style="padding:12px 0;">Tudo certo! Nenhum item em alerta. ✅</div>
                @endforelse
            </div>

            {{-- Alertas de Validade --}}
            <div class="rp-panel">
                <div class="rp-panel__title">⏰ Alertas de Validade</div>
                <div class="dash-alert-sub">Lotes vencidos ou próximos do vencimento</div>

                @forelse($expiringBatches as $b)
                    @php
                        $vencido = $b->days_left < 0;
                        $urgente = $b->days_left <= 7;
                        $bg = $vencido ? 'rgba(239,68,68,.15)' : ($urgente ? 'rgba(239,68,68,.12)' : 'rgba(245,200,66,.15)');
                    @endphp
                    <div class="dash-alert">
                        <span class="dash-alert__ico" style="background: {{ $bg }};">{{ $vencido ? '✕' : '⏰' }}</span>
                        <div class="dash-alert__info">
                            <div class="dash-alert__name">{{ $b->product->name ?? '—' }}</div>
                            <div class="dash-alert__status">
                                {{ $vencido ? 'Vencido' : 'Vence' }} em {{ $b->expiry_date->format('d/m/Y') }}{{ $b->lote ? ' • Lote '.$b->lote : '' }}
                            </div>
                        </div>
                        <div class="dash-alert__qty {{ $vencido ? 'text-danger' : '' }}">
                            <strong>{{ $vencido ? 'há '.abs($b->days_left) : $b->days_left }}</strong><span>dias</span>
                        </div>
                    </div>
                @empty
                    <div class="dash-alert-sub" style="padding:12px 0;">Nenhum lote próximo do vencimento. ✅</div>
                @endforelse

                <a href="{{ route('batches.index') }}" class="btn btn-outline-light btn-sm" style="margin-top:6px;">Ver validades →</a>
            </div>

            {{-- Entradas (6 meses) --}}
            <div class="rp-panel">
                <div class="rp-panel__title">📈 Entradas (6 meses)</div>
                <div style="height:160px;"><canvas id="chartEntradas"></canvas></div>
            </div>

            {{-- Por Categoria --}}
            <div class="rp-panel">
                <div class="rp-panel__title">🏷️ Por Categoria</div>
                <div class="dash-cat-wrap">
                    <div style="width:130px; height:130px; flex:0 0 auto;"><canvas id="chartCategoria"></canvas></div>
                    <div class="dash-cat-legend">
                        @foreach($porCategoria as $row)
                            <div class="dash-leg">
                                <span class="dash-leg__dot" style="background: {{ $catColor($row['cat']) }};"></span>
                                <span class="dash-leg__name">{{ $row['cat'] }}</span>
                                <span class="dash-leg__pct">{{ $row['pct'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .dash{ color: var(--t-text); }
    .dash-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .dash-title{ margin:0; font-weight:800; }
    .dash-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .dash-actions{ display:flex; gap:8px; }

    .dash-kpis{ display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-bottom:16px; }
    .rp-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; } .rp-card--red::before{ background:#ef4444; }
    .rp-card__label{ color: var(--t-muted); font-size:.82rem; }
    .rp-card__value{ font-size:1.9rem; font-weight:800; margin:6px 0 2px; }
    .rp-card__foot{ color: var(--t-muted); font-size:.78rem; }

    .dash-grid{ display:grid; grid-template-columns: 1.7fr 1fr; gap:14px; align-items:start; }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; overflow:hidden; }
    .dash-cat-head{ display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px; border-bottom:1px solid var(--t-border); flex-wrap:wrap; }
    .dash-cat-title{ font-weight:700; }
    .dash-cat-sub{ color: var(--t-muted); font-size:.82rem; }
    .dash-cat-search{ display:flex; gap:8px; }
    .rp-input{ height:38px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; min-width:160px; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-sel{ height:38px; padding:0 10px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); cursor:pointer; }

    /* tabela escura (vence o bootstrap) */
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; white-space:nowrap; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }

    .rp-prod{ display:flex; align-items:center; gap:10px; }
    .rp-ico{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 auto; }
    .rp-pname{ font-weight:600; }
    .rp-psku{ color: var(--t-muted); font-size:.78rem; }
    .rp-cat{ display:inline-block; padding:3px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
    .rp-qty{ display:flex; align-items:center; gap:10px; }
    .rp-bar{ width:64px; height:6px; border-radius:999px; background: var(--t-border); overflow:hidden; flex:0 0 auto; }
    .rp-bar__fill{ display:block; height:100%; border-radius:999px; }
    .rp-qty__n{ font-weight:600; }
    .rp-money{ color:#22c55e; font-weight:600; }

    .dash-dot{ display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px; }
    .dash-dot--green{ background:#22c55e; } .dash-dot--yellow{ background:#ffb700; } .dash-dot--red{ background:#ef4444; }

    .dash-acts{ display:inline-flex; gap:6px; justify-content:flex-end; }
    .dash-act{ width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background: var(--t-panel-2); border:1px solid var(--t-border); color: var(--t-muted); text-decoration:none; font-size:13px; cursor:pointer; padding:0; }
    .dash-edit{ display:inline-flex; align-items:center; gap:5px; height:30px; padding:0 12px; border-radius:8px; background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.40); color:#60a5fa; font-size:12px; font-weight:600; text-decoration:none; }
    .dash-edit:hover{ background:rgba(59,130,246,.28); }
    .dash-edit svg{ display:block; }
    .dash-act:hover{ border-color: var(--accent, #3b82f6); }
    .dash-act--del:hover{ border-color:#ef4444; }

    .dash-side{ display:flex; flex-direction:column; gap:14px; }
    .rp-panel{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; }
    .rp-panel__title{ font-weight:700; }
    .dash-alert-sub{ color: var(--t-muted); font-size:.82rem; margin:2px 0 12px; }
    .dash-alert{ display:flex; align-items:center; gap:12px; padding:10px; border:1px solid var(--t-border-soft); border-radius:12px; margin-bottom:8px; }
    .dash-alert__ico{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .dash-alert__info{ flex:1; min-width:0; }
    .dash-alert__name{ font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .dash-alert__status{ color: var(--t-muted); font-size:.78rem; }
    .dash-alert__qty{ text-align:right; }
    .dash-alert__qty strong{ font-size:1.05rem; } .dash-alert__qty span{ display:block; color: var(--t-muted); font-size:.7rem; }

    .dash-cat-wrap{ display:flex; align-items:center; gap:16px; margin-top:10px; }
    .dash-cat-legend{ flex:1; display:flex; flex-direction:column; gap:8px; }
    .dash-leg{ display:flex; align-items:center; gap:8px; font-size:.85rem; }
    .dash-leg__dot{ width:10px; height:10px; border-radius:50%; flex:0 0 auto; }
    .dash-leg__name{ flex:1; color: var(--t-text); }
    .dash-leg__pct{ color: var(--t-muted); font-weight:600; }

    @media (max-width: 1100px){
        .dash-grid{ grid-template-columns: 1fr; }
        .dash-kpis{ grid-template-columns: repeat(2, 1fr); }
    }
  .dash-act svg{ display:block; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  (function(){
    if (typeof Chart === 'undefined') return;
    var __isLight = document.documentElement.dataset.theme === 'light';
    var muted = __isLight ? '#6b7280' : '#94a3b8', grid = __isLight ? 'rgba(0,0,0,.08)' : 'rgba(255,255,255,.08)';

    var ent = document.getElementById('chartEntradas');
    if (ent) new Chart(ent, {
      type: 'bar',
      data: {
        labels: @json(collect($entradas)->pluck('label')),
        datasets: [{ data: @json(collect($entradas)->pluck('total')), backgroundColor: '#7c5cfc', borderRadius: 6 }]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{ x:{ ticks:{ color:muted }, grid:{ display:false } }, y:{ ticks:{ color:muted, precision:0 }, grid:{ color:grid } } }
      }
    });

    var cat = document.getElementById('chartCategoria');
    if (cat) new Chart(cat, {
      type: 'doughnut',
      data: {
        labels: @json($porCategoria->pluck('cat')),
        datasets: [{ data: @json($porCategoria->pluck('total')), backgroundColor: @json($porCategoria->map(fn($r) => $catColor($r['cat']))->values()), borderWidth:0 }]
      },
      options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{ legend:{ display:false } } }
    });
  })();
</script>

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

{{-- ===== Mini tela de detalhes (mobile) ===== --}}
<div class="dvm-overlay" id="dvmOverlay">
  <div class="dvm-box">
    <div class="dvm-head">
      <div>
        <div class="dvm-name" id="dvmName">—</div>
        <div class="dvm-code" id="dvmCode">—</div>
      </div>
      <button type="button" class="dvm-close" id="dvmClose">✕</button>
    </div>
    <div class="dvm-rows">
      <div><span>Categoria</span><strong id="dvmCategory">—</strong></div>
      <div><span>Qtd. estoque</span><strong id="dvmQty">—</strong></div>
      <div><span>Preço unit.</span><strong id="dvmPrice">—</strong></div>
      <div><span>Valor total</span><strong id="dvmValue" class="g">—</strong></div>
      <div><span>Status</span><strong id="dvmStatus">—</strong></div>
    </div>
  </div>
</div>

<style>
  /* Mini modal mobile */
  .dvm-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.55); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:2000; }
  .dvm-overlay.open{ display:flex; }
  .dvm-box{ background:var(--t-panel-2); border:1px solid var(--t-border); border-radius:16px; padding:20px; width:92%; max-width:380px; color:var(--t-text); box-shadow:var(--t-shadow); }
  .dvm-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px; }
  .dvm-name{ font-weight:800; font-size:1.05rem; color:var(--t-text); }
  .dvm-code{ color:var(--t-muted); font-size:.8rem; margin-top:2px; }
  .dvm-close{ background:var(--t-input-bg); border:1px solid var(--t-input-border); color:var(--t-text); width:30px; height:30px; border-radius:8px; cursor:pointer; flex:0 0 auto; }
  .dvm-rows > div{ display:flex; justify-content:space-between; gap:12px; padding:10px 2px; border-bottom:1px solid var(--t-border-soft); font-size:.9rem; }
  .dvm-rows > div:last-child{ border-bottom:0; }
  .dvm-rows span{ color:var(--t-muted); }
  .dvm-rows strong{ text-align:right; color:var(--t-text); }
  .dvm-rows strong.g{ color:#22c55e; }

  /* Olho mobile: escondido no desktop */
  .dash-eye-m{ display:none; }

  @media (max-width: 992px){
    /* Catálogo enxuto: só Produto + Qtd + olho */
    .dash-tbl th:nth-child(2), .dash-tbl td:nth-child(2),
    .dash-tbl th:nth-child(4), .dash-tbl td:nth-child(4),
    .dash-tbl th:nth-child(5), .dash-tbl td:nth-child(5),
    .dash-tbl th:nth-child(6), .dash-tbl td:nth-child(6){ display:none; }
    .dash-tbl{ min-width:0 !important; }

    /* Ações: só o olho-modal */
    .dash-tbl .dash-act, .dash-tbl .dash-edit, .dash-tbl .dash-acts form{ display:none !important; }
    .dash-eye-m{ display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background: var(--t-panel-2); border:1px solid var(--t-border); color: var(--t-muted); cursor:pointer; }

    /* Busca + categorias do catálogo: cada um na sua linha, largura total */
    .dash-cat-search{ width:100%; display:flex; flex-wrap:wrap; gap:8px; }
    .dash-cat-search .rp-input{ flex:1 1 100%; min-width:0; }
    .dash-cat-search .rp-sel{ flex:1 1 100%; min-width:0; }
  }
</style>

<script>
(function(){
  var overlay=document.getElementById('dvmOverlay');
  function set(id,val){ var el=document.getElementById(id); if(el) el.textContent=val; }
  function closeM(){ if(overlay) overlay.classList.remove('open'); }
  var c=document.getElementById('dvmClose'); if(c) c.addEventListener('click', closeM);
  if(overlay) overlay.addEventListener('click', function(e){ if(e.target===overlay) closeM(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeM(); });
  document.querySelectorAll('.dash-eye-m').forEach(function(btn){
    btn.addEventListener('click', function(){
      set('dvmName', btn.dataset.name); set('dvmCode', btn.dataset.code);
      set('dvmCategory', btn.dataset.category); set('dvmQty', btn.dataset.qty);
      set('dvmPrice', btn.dataset.price); set('dvmValue', btn.dataset.value);
      var st=document.getElementById('dvmStatus');
      if(st){ st.textContent=btn.dataset.status;
        st.style.color = btn.dataset.status==='Sem estoque' ? '#ef4444' : (btn.dataset.status==='Estoque baixo' ? '#ffb700' : '#22c55e'); }
      if(overlay) overlay.classList.add('open');
    });
  });
})();
</script>
@endsection

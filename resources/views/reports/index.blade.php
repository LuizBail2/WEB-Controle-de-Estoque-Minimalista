@extends('layouts.app')

@section('title', 'Relatórios')

@php
    $total = (int) $stats->total;
    $pct = fn ($n) => $total > 0 ? round($n / $total * 100) : 0;
    $exportParams = request()->only(['q', 'category', 'status', 'sort']);

    $palette = ['#3b82f6','#22c55e','#7c5cfc','#ffb700','#ef4444','#06b6d4','#ec4899','#f97316'];
    $catColor = function ($name) use ($categoryColors, $palette) {
        if (!$name) return '#94a3b8';
        return $categoryColors[$name] ?? $palette[abs(crc32($name)) % count($palette)];
    };

    // Cabeçalho clicável de ordenação (alterna asc/desc e preserva os filtros)
    $sortHeader = function ($col, $label) {
        $cur = request('sort', 'name_asc');
        if ($cur === '') $cur = 'name_asc';
        $asc = $col . '_asc';
        $desc = $col . '_desc';
        $active = ($cur === $asc || $cur === $desc);
        $next = ($cur === $asc) ? $desc : $asc;
        $arrow = $active ? ($cur === $asc ? ' ↑' : ' ↓') : '';
        $url = request()->fullUrlWithQuery(['sort' => $next, 'page' => null]);
        return '<a href="' . e($url) . '" class="rp-sort' . ($active ? ' active' : '') . '">' . e($label) . $arrow . '</a>';
    };
@endphp

@section('content')
<div class="reports-dark">

    {{-- Cabeçalho --}}
    <div class="rp-header">
        <div>
            <h3 class="rp-title">Relatórios</h3>
            <p class="rp-sub">Analise e exporte o status completo do seu estoque</p>
        </div>
        <div class="rp-actions">
            <a href="{{ route('reports.export', $exportParams) }}" class="btn btn-outline-light btn-sm">📥 Exportar CSV</a>
            <a href="{{ route('reports.pdf', $exportParams) }}" class="btn btn-primary btn-sm" target="_blank">🖨️ Gerar Relatório PDF</a>
            <a href="{{ route('reports.returns') }}" class="btn btn-outline-light btn-sm">↩ Devoluções</a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="rp-kpis">
        <div class="rp-card rp-card--blue">
            <div class="rp-card__label">Total de produtos</div>
            <div class="rp-card__value">{{ $total }}</div>
            <div class="rp-card__foot">no catálogo</div>
        </div>
        <div class="rp-card rp-card--green">
            <div class="rp-card__label">✔ Em estoque (OK)</div>
            <div class="rp-card__value">{{ $stats->ok }}</div>
            <div class="rp-card__foot">{{ $pct($stats->ok) }}% do catálogo</div>
        </div>
        <div class="rp-card rp-card--yellow">
            <div class="rp-card__label">⚠ Estoque baixo</div>
            <div class="rp-card__value">{{ $stats->baixo }}</div>
            <div class="rp-card__foot">{{ $pct($stats->baixo) }}% do catálogo</div>
        </div>
        <div class="rp-card rp-card--red">
            <div class="rp-card__label">✕ Sem estoque</div>
            <div class="rp-card__value">{{ $stats->sem }}</div>
            <div class="rp-card__foot">{{ $pct($stats->sem) }}% do catálogo</div>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="rp-charts">
        <div class="rp-panel">
            <div class="rp-panel__title">📊 Produtos por Categoria</div>
            <div style="height:280px;"><canvas id="chartCategorias"></canvas></div>
        </div>

        <div class="rp-panel">
            <div class="rp-panel__title">💰 Valor por Status</div>
            <div style="height:200px;"><canvas id="chartValor"></canvas></div>
            <div class="rp-legend">
                <div><span class="dot" style="background:#22c55e"></span> Em Estoque <strong>R$ {{ number_format($stats->valor_ok, 2, ',', '.') }}</strong></div>
                <div><span class="dot" style="background:#ffb700"></span> Estoque Baixo <strong>R$ {{ number_format($stats->valor_baixo, 2, ',', '.') }}</strong></div>
                <div class="rp-legend__total">Valor total em estoque: <strong>R$ {{ number_format($stats->valor_total, 2, ',', '.') }}</strong></div>
            </div>
        </div>
    </div>

    {{-- Abas por status (com contador) --}}
    <div class="rp-tabs">
        @php $st = request('status'); @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="rp-tab {{ !$st ? 'active' : '' }}">📋 Todos os Produtos <span class="cnt">{{ $stats->total }}</span></a>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'ok', 'page' => null]) }}" class="rp-tab rp-tab--green {{ $st === 'ok' ? 'active' : '' }}">✓ Em Estoque <span class="cnt">{{ $stats->ok }}</span></a>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'low', 'page' => null]) }}" class="rp-tab rp-tab--yellow {{ $st === 'low' ? 'active' : '' }}">⚠ Estoque Baixo <span class="cnt">{{ $stats->baixo }}</span></a>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'out', 'page' => null]) }}" class="rp-tab rp-tab--red {{ $st === 'out' ? 'active' : '' }}">✕ Sem Estoque <span class="cnt">{{ $stats->sem }}</span></a>
    </div>

    {{-- Pedidos de Compra (solicitações + PDF) --}}
    @php
        $poReports = \App\Models\PurchaseOrder::with(['supplier', 'creator'])->latest()->limit(100)->get();
    @endphp
    <div class="rp-report" style="margin-bottom:16px;">
        <div style="padding:14px 18px; border-bottom:1px solid var(--t-border); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <strong>🧾 Pedidos de Compra — solicitações</strong>
            <span style="color:var(--t-muted); font-size:.85rem;">PDF de cada solicitação disponível para download</span>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:.9rem;">
            <thead>
                <tr style="text-align:left; color:var(--t-muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.03em;">
                    <th style="padding:10px 14px;">Pedido</th>
                    <th style="padding:10px 14px;">Fornecedor</th>
                    <th style="padding:10px 14px;">Solicitado por</th>
                    <th style="padding:10px 14px;">Data</th>
                    <th style="padding:10px 14px;">Situação</th>
                    <th style="padding:10px 14px; text-align:right;">PDF</th>
                </tr>
            </thead>
            <tbody>
            @forelse($poReports as $po)
                <tr style="border-top:1px solid var(--t-border-soft);">
                    <td style="padding:10px 14px; font-weight:600;">{{ $po->code }}</td>
                    <td style="padding:10px 14px;">{{ $po->supplier->name ?? '—' }}</td>
                    <td style="padding:10px 14px;">{{ optional($po->creator)->name ?? '—' }}</td>
                    <td style="padding:10px 14px;">{{ $po->created_at?->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 14px;">
                        @if($po->isApproved())
                            <span style="color:#22c55e;">✓ Aprovado</span>
                        @elseif($po->isRejected())
                            <span style="color:#ef4444;">✕ Rejeitado</span>
                        @else
                            <span style="color:#ffb700;">⏳ Aguardando</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px; text-align:right;">
                        <a href="{{ route('purchase-orders.report', $po) }}" target="_blank" style="color:var(--accent,#3b82f6); text-decoration:none;">📄 Baixar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:14px; color:var(--t-muted);">Nenhum pedido de compra ainda.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="rp-report">
        <form method="GET" action="{{ route('reports.index') }}" class="rp-toolbar" id="rpFilterForm">
            <input type="hidden" name="status" value="{{ request('status') }}">

            <div class="rp-search">
                <span class="rp-search__ico">🔍</span>
                <input type="text" name="q" value="{{ request('q') }}" class="rp-input" placeholder="Buscar por nome, SKU, categoria ou fornecedor...">
            </div>

            <select name="category" class="rp-sel" onchange="this.form.submit()">
                <option value="">Todas as categorias</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>

            <select name="sort" class="rp-sel" onchange="this.form.submit()">
                <option value="name_asc" {{ in_array(request('sort'), ['', 'name_asc'], true) ? 'selected' : '' }}>Ordenar: Nome A–Z</option>
                <option value="quantity_asc" {{ request('sort') === 'quantity_asc' ? 'selected' : '' }}>Qtd. ↑ Crescente</option>
                <option value="quantity_desc" {{ request('sort') === 'quantity_desc' ? 'selected' : '' }}>Qtd. ↓ Decrescente</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Preço ↓ Maior</option>
                <option value="value_desc" {{ request('sort') === 'value_desc' ? 'selected' : '' }}>Valor Total ↓</option>
            </select>

            <div class="rp-toolbar-right">
                <a href="{{ route('reports.export', $exportParams) }}" class="rp-icon-btn" title="Exportar CSV">📥</a>
                <a href="{{ route('reports.pdf', $exportParams) }}" target="_blank" class="rp-icon-btn" title="Gerar Relatório PDF">🖨️</a>
            </div>
        </form>

        <div class="rp-bulk" id="rpBulk" style="display:none;">
            <span><strong id="rpBulkCount">0</strong> produto(s) selecionado(s)</span>
            <button type="button" class="btn btn-sm btn-primary" id="rpBulkExport">📥 Exportar selecionados (CSV)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="rpBulkClear">Limpar seleção</button>
        </div>

        <div class="table-responsive">
            <table class="table rp-table rp-mob align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="rp-check rp-check-all" title="Selecionar todos"></th>
                        <th class="rp-th">{!! $sortHeader('name', 'Produto / SKU') !!}</th>
                        <th class="rp-th">{!! $sortHeader('category', 'Categoria') !!}</th>
                        <th class="rp-th">{!! $sortHeader('quantity', 'Quantidade') !!}</th>
                        <th class="rp-th text-end">{!! $sortHeader('minimum', 'Qtd. Mínima') !!}</th>
                        <th class="rp-th text-end">{!! $sortHeader('price', 'Preço Unit.') !!}</th>
                        <th class="rp-th text-end">{!! $sortHeader('value', 'Valor em Estoque') !!}</th>
                        <th>Status</th>
                        <th class="rp-th">{!! $sortHeader('supplier', 'Fornecedor') !!}</th>
                        <th class="rp-eye"></th>
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
                            <td><input type="checkbox" class="rp-check rp-row-check" value="{{ $p->id }}"></td>
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
                                @else
                                    <span style="color:var(--t-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="rp-qty">
                                    <span class="rp-bar"><span class="rp-bar__fill" style="width: {{ $fill }}%; background: {{ $barColor }};"></span></span>
                                    <span class="rp-qty__n">{{ $p->quantity }}</span>
                                </div>
                            </td>
                            <td class="text-end">{{ $p->minimum_quantity }}</td>
                            <td class="text-end">R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</td>
                            <td class="text-end">
                                @if($val > 0)
                                    <span class="rp-money">R$ {{ number_format($val, 2, ',', '.') }}</span>
                                @else
                                    <span style="color:var(--t-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isOut)
                                    <span class="rp-badge rp-badge--red">✕ Zerado</span>
                                @elseif($isLow)
                                    <span class="rp-badge rp-badge--yellow">⚠ Baixo</span>
                                @else
                                    <span class="rp-badge rp-badge--green">✓ OK</span>
                                @endif
                            </td>
                            <td>{{ $p->supplier ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4" style="color:var(--t-muted)">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== MOBILE: cards com olho que expande ===== --}}
        <div class="rp-mobile">
            @forelse($products as $p)
                @php
                    $isOut = $p->quantity == 0;
                    $isLow = $p->quantity > 0 && $p->quantity <= $p->minimum_quantity;
                    $c = $catColor($p->category);
                    $val = ($p->price ?? 0) * $p->quantity;
                @endphp
                <div class="rpm-card">
                    <div class="rpm-head" data-rpm-toggle>
                        <span class="rp-ico" style="background: {{ $c }}22; color: {{ $c }};">📦</span>
                        <div class="rpm-main">
                            <div class="rp-pname">{{ $p->name }}</div>
                            @if($p->category)
                                <span class="rp-cat" style="background: {{ $c }}22; color: {{ $c }};">{{ $p->category }}</span>
                            @else
                                <span class="rpm-nocat">Sem categoria</span>
                            @endif
                        </div>
                        <button type="button" class="rpm-eye" aria-label="Ver detalhes">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="rpm-detail">
                        <div class="rpm-row"><span>Quantidade</span><strong>{{ $p->quantity }}</strong></div>
                        <div class="rpm-row"><span>Qtd. mínima</span><strong>{{ $p->minimum_quantity }}</strong></div>
                        <div class="rpm-row"><span>Preço unit.</span><strong>R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</strong></div>
                        <div class="rpm-row"><span>Valor em estoque</span><strong class="rp-money">{{ $val > 0 ? 'R$ '.number_format($val, 2, ',', '.') : '—' }}</strong></div>
                        <div class="rpm-row"><span>Status</span>
                            @if($isOut)<span class="rp-badge rp-badge--red">✕ Zerado</span>
                            @elseif($isLow)<span class="rp-badge rp-badge--yellow">⚠ Baixo</span>
                            @else<span class="rp-badge rp-badge--green">✓ OK</span>@endif
                        </div>
                        <div class="rpm-row"><span>Fornecedor</span><strong>{{ $p->supplier ?: '—' }}</strong></div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4" style="color:var(--t-muted)">Nenhum produto encontrado.</div>
            @endforelse
        </div>

        <div class="rp-pagination">
            <div class="rp-pag-info">
                Mostrando {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} de {{ $products->total() }} produtos
            </div>
            <div>{{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

<style>
    .reports-dark{ color: var(--t-text); }
    .rp-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .rp-title{ margin:0; font-weight:800; }
    .rp-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .rp-actions{ display:flex; gap:8px; }

    .rp-kpis{ display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-bottom:16px; }
    .rp-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; } .rp-card--red::before{ background:#ef4444; }
    .rp-card__label{ color: var(--t-muted); font-size:.82rem; }
    .rp-card__value{ font-size:1.9rem; font-weight:800; margin:6px 0 2px; }
    .rp-card__foot{ color: var(--t-muted); font-size:.78rem; }

    .rp-charts{ display:grid; grid-template-columns: 1.4fr 1fr; gap:14px; margin-bottom:16px; }
    .rp-panel{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; }
    .rp-panel__title{ font-weight:700; margin-bottom:14px; }
    .rp-legend{ margin-top:14px; display:flex; flex-direction:column; gap:6px; font-size:.88rem; color: var(--t-muted); }
    .rp-legend .dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
    .rp-legend strong{ color: var(--t-text); }
    .rp-legend__total{ margin-top:6px; padding-top:8px; border-top:1px solid var(--t-border); }

    .rp-tabs{ display:flex; gap:4px; flex-wrap:wrap; }
    .rp-tab{ padding:10px 16px; color: var(--t-muted); text-decoration:none; border-bottom:2px solid transparent; font-size:.9rem; }
    .rp-tab .cnt{ display:inline-block; margin-left:6px; padding:1px 9px; border-radius:999px; background: var(--t-hover); font-size:.75rem; font-weight:700; }
    .rp-tab.active{ color: var(--accent, #3b82f6); border-bottom-color: var(--accent, #3b82f6); font-weight:600; }
    .rp-tab.active .cnt{ background: rgba(59,130,246,.25); color:#fff; }
    .rp-tab--green.active{ color:#22c55e; border-bottom-color:#22c55e; } .rp-tab--green.active .cnt{ background:rgba(34,197,94,.25); }
    .rp-tab--yellow.active{ color:#ffb700; border-bottom-color:#ffb700; } .rp-tab--yellow.active .cnt{ background:rgba(245,200,66,.25); }
    .rp-tab--red.active{ color:#ef4444; border-bottom-color:#ef4444; } .rp-tab--red.active .cnt{ background:rgba(239,68,68,.25); }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:0 12px 12px 12px; overflow:hidden; }
    .rp-toolbar{ display:flex; align-items:center; gap:10px; padding:14px 18px; flex-wrap:wrap; border-bottom:1px solid var(--t-border); }
    .rp-search{ position:relative; flex:1; min-width:220px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:40px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-input:focus{ border-color: var(--accent, #3b82f6); }
    .rp-sel{ height:40px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text);  cursor:pointer; }
    .rp-toolbar-right{ display:flex; gap:8px; margin-left:auto; }
    .rp-icon-btn{ width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); text-decoration:none; font-size:16px; }
    .rp-icon-btn:hover{ border-color: var(--accent, #3b82f6); background: rgba(59,130,246,.12); }

    /* cabeçalhos clicáveis (ordenação) */
    .rp-th{ cursor:pointer; }
    .rp-sort{ color: var(--t-muted) !important; text-decoration:none; white-space:nowrap; }
    .rp-sort:hover{ color: var(--t-text) !important; }
    .rp-sort.active{ color: var(--accent, #3b82f6) !important; font-weight:700; }

    /* TABELA ESCURA — forca transparente com !important pra vencer o Bootstrap e o dashboard-pro.css */
    .rp-table, .rp-table > :not(caption) > * > *{
        background-color: transparent !important;
        color: var(--t-text) !important;
        box-shadow: none !important;
    }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; white-space:nowrap; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }

    /* Checkbox escuro (igual a referencia) */
    .rp-check{
        appearance:none; -webkit-appearance:none;
        width:18px; height:18px; border-radius:5px;
        border:1px solid var(--t-input-border);
        background: var(--t-panel-2) !important;
        cursor:pointer; position:relative; flex:0 0 auto; vertical-align:middle; margin:0;
    }
    .rp-check:checked{ background: var(--accent, #3b82f6) !important; border-color: var(--accent, #3b82f6); }
    .rp-check:checked::after{
        content:''; position:absolute; left:5px; top:1px; width:5px; height:9px;
        border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg);
    }

    /* Barra de acoes em massa */
    .rp-bulk{ display:flex; align-items:center; gap:12px; padding:10px 16px; background: rgba(59,130,246,.10); border-bottom:1px solid var(--t-border); font-size:.88rem; }
    .rp-bulk strong{ color: var(--accent, #3b82f6); }

    .rp-prod{ display:flex; align-items:center; gap:10px; }
    .rp-ico{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 auto; }
    .rp-pname{ font-weight:600; }
    .rp-psku{ color: var(--t-muted); font-size:.78rem; }

    .rp-cat{ display:inline-block; padding:3px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }

    .rp-qty{ display:flex; align-items:center; gap:10px; }
    .rp-bar{ width:70px; height:6px; border-radius:999px; background: var(--t-border); overflow:hidden; flex:0 0 auto; }
    .rp-bar__fill{ display:block; height:100%; border-radius:999px; }
    .rp-qty__n{ font-weight:600; }

    .rp-money{ color:#22c55e; font-weight:600; }

    .rp-badge{ display:inline-block; padding:3px 10px; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; }
    .rp-badge--green{ background:rgba(34,197,94,.15); color:#22c55e; }
    .rp-badge--yellow{ background:rgba(245,200,66,.15); color:#ffb700; }
    .rp-badge--red{ background:rgba(239,68,68,.15); color:#ef4444; }

    .rp-pagination{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; flex-wrap:wrap; gap:10px; }
    .rp-pag-info{ color: var(--t-muted); font-size:.85rem; }
    .rp-pagination .pagination{ margin:0; }
    .rp-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .rp-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .rp-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }

    @media (max-width: 900px){
        .rp-kpis{ grid-template-columns: repeat(2, 1fr); }
        .rp-charts{ grid-template-columns: 1fr; }
    }

    /* ===== Mobile: cards com olho (ponto 3) ===== */
    .rp-mobile{ display:none; }
    @media (max-width: 768px){
        .rp-table.rp-mob, .rp-report .table-responsive{ display:none; }   /* esconde a tabela no celular */
        .rp-mobile{ display:block; padding:10px; }

        .rpm-card{ background: var(--t-panel-2); border:1px solid var(--t-border); border-radius:14px; margin-bottom:12px; overflow:hidden; }
        .rpm-head{ display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer; }
        .rpm-main{ flex:1; min-width:0; }
        .rpm-main .rp-pname{ font-weight:700; }
        .rpm-nocat{ color:var(--t-muted); font-size:.78rem; }
        .rpm-eye{ width:38px; height:38px; flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background: var(--t-hover); border:1px solid var(--t-input-border); color: var(--t-muted); cursor:pointer; }
        .rpm-card.open .rpm-eye{ color: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); background: rgba(59,130,246,.12); }
        .rpm-detail{ display:none; padding:0 14px 12px; border-top:1px solid var(--t-border-soft); }
        .rpm-card.open .rpm-detail{ display:block; }
        .rpm-row{ display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--t-border-soft); }
        .rpm-row:last-child{ border-bottom:0; }
        .rpm-row span{ color: var(--t-muted); font-size:.84rem; }
        .rpm-row strong{ color: var(--t-text); }
    }

    /* ===== Ponto 4: remove a paginação numerada duplicada no celular ===== */
    @media (max-width: 575.98px){
        .rp-pagination .d-sm-flex{ display:none !important; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  (function(){
    if (typeof Chart === 'undefined') return;
    var __isLight = document.documentElement.dataset.theme === 'light';
    var muted = __isLight ? '#6b7280' : '#94a3b8', grid = __isLight ? 'rgba(0,0,0,.08)' : 'rgba(255,255,255,.08)';

    var catLabels = @json($porCategoria->pluck('cat'));
    var catData   = @json($porCategoria->pluck('total'));

    var c1 = document.getElementById('chartCategorias');
    if (c1) new Chart(c1, {
      type: 'bar',
      data: { labels: catLabels, datasets: [{ data: catData, backgroundColor: '#7c5cfc', borderRadius: 6 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: muted }, grid: { display: false } },
          y: { ticks: { color: muted, precision: 0 }, grid: { color: grid } }
        }
      }
    });

    var c2 = document.getElementById('chartValor');
    if (c2) new Chart(c2, {
      type: 'doughnut',
      data: {
        labels: ['Em Estoque', 'Estoque Baixo', 'Sem Estoque'],
        datasets: [{ data: [{{ $stats->valor_ok }}, {{ $stats->valor_baixo }}, 0], backgroundColor: ['#22c55e', '#ffb700', '#ef4444'], borderWidth: 0 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '62%',
        plugins: { legend: { display: false } }
      }
    });
  })();
</script>

<script>
  (function(){
    var all   = document.querySelector('.rp-check-all');
    var bulk  = document.getElementById('rpBulk');
    var count = document.getElementById('rpBulkCount');
    var getRows = function(){ return Array.prototype.slice.call(document.querySelectorAll('.rp-row-check')); };

    function update(){
      var rows = getRows();
      var checked = rows.filter(function(c){ return c.checked; });
      if (count) count.textContent = checked.length;
      if (bulk)  bulk.style.display = checked.length ? 'flex' : 'none';
      if (all)   all.checked = checked.length > 0 && checked.length === rows.length;
    }

    if (all) all.addEventListener('change', function(){
      getRows().forEach(function(c){ c.checked = all.checked; });
      update();
    });
    getRows().forEach(function(c){ c.addEventListener('change', update); });

    var exp = document.getElementById('rpBulkExport');
    if (exp) exp.addEventListener('click', function(){
      var ids = getRows().filter(function(c){ return c.checked; }).map(function(c){ return c.value; });
      if (!ids.length) return;
      var qs = ids.map(function(id){ return 'ids[]=' + encodeURIComponent(id); }).join('&');
      window.location = "{{ route('reports.export') }}?" + qs;
    });

    var clr = document.getElementById('rpBulkClear');
    if (clr) clr.addEventListener('click', function(){
      getRows().forEach(function(c){ c.checked = false; });
      if (all) all.checked = false;
      update();
    });
  })();
</script>

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

<script>
  // Mobile: expandir/recolher card ao tocar (ponto 3)
  document.querySelectorAll('.rp-mobile [data-rpm-toggle]').forEach(function(head){
    head.addEventListener('click', function(){
      head.closest('.rpm-card').classList.toggle('open');
    });
  });
</script>
@endsection
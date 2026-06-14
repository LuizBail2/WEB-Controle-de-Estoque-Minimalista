@extends('layouts.app')

@section('title', 'Produtos')

@php
    $total = (int) $stats->total;
    $palette = ['#3b82f6','#22c55e','#7c5cfc','#ffb700','#ef4444','#06b6d4','#ec4899','#f97316'];
    $catColor = function ($name) use ($categoryColors, $palette) {
        if (!$name) return '#94a3b8';
        return $categoryColors[$name] ?? $palette[abs(crc32($name)) % count($palette)];
    };
    $sortHeader = function ($col, $label) {
        $cur = request('sort', 'name_asc'); if ($cur === '') $cur = 'name_asc';
        $asc = $col.'_asc'; $desc = $col.'_desc';
        $active = ($cur === $asc || $cur === $desc);
        $next = ($cur === $asc) ? $desc : $asc;
        $arrow = $active ? ($cur === $asc ? ' ↑' : ' ↓') : ' ↕';
        $url = request()->fullUrlWithQuery(['sort' => $next, 'page' => null]);
        return '<a href="'.e($url).'" class="rp-sort'.($active ? ' active' : '').'">'.e($label).$arrow.'</a>';
    };
    $exportParams = ['q' => request('search'), 'category' => request('category'), 'status' => request('status'), 'sort' => request('sort')];
@endphp

@section('content')
<div class="reports-dark prod">

    {{-- Cabeçalho --}}
    <div class="prod-header">
        <div>
            <h3 class="prod-title">Produtos</h3>
            <p class="prod-sub">Gerencie seu catálogo de produtos e estoque</p>
        </div>
        <div class="prod-actions">
            <a href="{{ route('reports.lowstock') }}" class="btn btn-outline-warning btn-sm">📄 PDF: Estoque Baixo</a>
            <a href="{{ route('reports.export', $exportParams) }}" class="btn btn-outline-light btn-sm">📥 Exportar CSV</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newProductModal">+ Novo Produto</button>
        </div>
    </div>

    {{-- Cards --}}
    <div class="prod-kpis">
        <div class="rp-card rp-card--blue"><div class="rp-card__label">Total de produtos</div><div class="rp-card__value">{{ $total }}</div><div class="rp-card__foot">itens cadastrados</div></div>
        <div class="rp-card rp-card--green"><div class="rp-card__label">Valor em estoque</div><div class="rp-card__value" style="font-size:1.4rem;">R$ {{ number_format($stats->valor, 2, ',', '.') }}</div><div class="rp-card__foot">valor total</div></div>
        <div class="rp-card rp-card--green"><div class="rp-card__label">Em estoque</div><div class="rp-card__value">{{ $stats->ok }}</div><div class="rp-card__foot">produtos OK</div></div>
        <div class="rp-card rp-card--yellow"><div class="rp-card__label">Estoque baixo</div><div class="rp-card__value">{{ $stats->baixo }}</div><div class="rp-card__foot">abaixo do mínimo</div></div>
        <div class="rp-card rp-card--red"><div class="rp-card__label">Sem estoque</div><div class="rp-card__value">{{ $stats->sem }}</div><div class="rp-card__foot">reposição urgente</div></div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('products.index') }}" class="prod-filters">
        <div class="rp-search">
            <span class="rp-search__ico">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" class="rp-input" placeholder="Pesquisar produto, SKU ou categoria...">
        </div>
        <select name="category" class="rp-sel js-search" style="min-width:180px;">
            <option value="">Todas as categorias</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
        <select name="status" class="rp-sel">
            <option value="">Todos os status</option>
            <option value="ok"  {{ request('status') === 'ok'  ? 'selected' : '' }}>Em estoque</option>
            <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Estoque baixo</option>
            <option value="out" {{ request('status') === 'out' ? 'selected' : '' }}>Sem estoque</option>
        </select>
        <button class="btn btn-primary" type="submit">🔍 Buscar</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Limpar</a>
    </form>

    {{-- Catálogo --}}
    <div class="rp-report">
        <div class="prod-cat-head">
            <div>
                <div class="prod-cat-title">Catálogo de Produtos</div>
                <div class="prod-cat-sub">{{ $products->total() }} produto(s) encontrado(s)</div>
            </div>

            {{-- Botão COLUNAS (preferência) --}}
            <div class="cols-wrap">
                <button type="button" class="btn btn-outline-light btn-sm" id="colsBtn">⚙ Colunas</button>
                <div class="cols-menu" id="colsMenu">
                    <div class="cols-menu__title">Mostrar colunas</div>
                    <label><input type="checkbox" class="col-toggle" data-col="categoria" checked> Categoria</label>
                    <label><input type="checkbox" class="col-toggle" data-col="quantidade" checked> Quantidade</label>
                    <label><input type="checkbox" class="col-toggle" data-col="minimo" checked> Qtd. Mínima</label>
                    <label><input type="checkbox" class="col-toggle" data-col="preco" checked> Preço</label>
                    <label><input type="checkbox" class="col-toggle" data-col="valor" checked> Valor Total</label>
                    <label><input type="checkbox" class="col-toggle" data-col="status" checked> Status</label>
                    <label><input type="checkbox" class="col-toggle" data-col="acoes" checked> Ações</label>
                </div>
            </div>
        </div>

        {{-- Barra de seleção --}}
        <div class="rp-bulk" id="rpBulk" style="display:none;">
            <span><strong id="rpBulkCount">0</strong> produto(s) selecionado(s)</span>
            <a href="#" class="btn btn-sm btn-primary" id="rpBulkExport">📥 Exportar selecionados (CSV)</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="rpBulkClear">Limpar seleção</button>
        </div>

        <div class="table-responsive">
            <table class="table rp-table align-middle mb-0" data-mob="cards">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="rp-check rp-check-all"></th>
                        <th class="rp-th">{!! $sortHeader('name', 'Nome') !!}</th>
                        <th class="rp-th col-categoria">{!! $sortHeader('category', 'Categoria') !!}</th>
                        <th class="rp-th col-quantidade">{!! $sortHeader('quantity', 'Quantidade') !!}</th>
                        <th class="rp-th col-minimo text-end">{!! $sortHeader('minimum', 'Qtd. Mínima') !!}</th>
                        <th class="rp-th col-preco text-end">{!! $sortHeader('price', 'Preço') !!}</th>
                        <th class="rp-th col-valor text-end">{!! $sortHeader('value', 'Valor Total') !!}</th>
                        <th class="col-status">Status</th>
                        <th class="col-acoes">Ações</th>
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
                                    <div><div class="rp-pname">{{ $p->name }}</div><div class="rp-psku">{{ $p->code ?: '—' }}</div></div>
                                </div>
                            </td>
                            <td class="col-categoria" data-label="Categoria">
                                @if($p->category)<span class="rp-cat" style="background: {{ $c }}22; color: {{ $c }};">{{ $p->category }}</span>
                                @else <span style="color:var(--t-muted)">—</span> @endif
                            </td>
                            <td class="col-quantidade" data-label="Quantidade">
                                <div class="rp-qty">
                                    <span class="rp-bar"><span class="rp-bar__fill" style="width: {{ $fill }}%; background: {{ $barColor }};"></span></span>
                                    <span class="rp-qty__n">{{ $p->quantity }}</span>
                                </div>
                            </td>
                            <td class="col-minimo text-end" data-label="Qtd. mínima">{{ $p->minimum_quantity }}</td>
                            <td class="col-preco text-end" data-label="Preço">R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</td>
                            <td class="col-valor text-end" data-label="Valor total">
                                @if($val > 0)<span class="rp-money">R$ {{ number_format($val, 2, ',', '.') }}</span>
                                @else <span style="color:var(--t-muted)">—</span> @endif
                            </td>
                            <td class="col-status" data-label="Status">
                                @if($isOut)<span class="rp-badge rp-badge--red">✕ Sem estoque</span>
                                @elseif($isLow)<span class="rp-badge rp-badge--yellow">⚠ Baixo</span>
                                @else <span class="rp-badge rp-badge--green">✓ OK</span> @endif
                            </td>
                            <td class="col-acoes" data-label="Ações">
                                <div class="acts-cell">
                                    <button type="button" class="act-btn act-view js-view" data-show="{{ route('products.show', $p) }}" title="Ver detalhes">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button type="button" class="act-btn act-edit js-edit" data-show="{{ route('products.show', $p) }}">✏️ Editar</button>
                                    <button type="button" class="act-btn act-del js-delete" data-name="{{ e($p->name) }}" data-url="{{ route('products.destroy', $p) }}">🗑️ Excluir</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4" style="color:var(--t-muted)">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rp-pagination">
            <div class="rp-pag-info">Mostrando {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} de {{ $products->total() }}</div>
            <div>{{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

{{-- ===== PAINEL LATERAL: Detalhes do Produto ===== --}}
<div class="pd-overlay" id="pdOverlay"></div>
<aside class="pd-drawer" id="pdDrawer" aria-hidden="true">
  <div class="pd-head">
    <h5 class="pd-title">Detalhes do Produto</h5>
    <button type="button" class="pd-close" id="pdClose">✕</button>
  </div>

  <div class="pd-card">
    <div class="pd-ico" id="pdIco">📦</div>
    <div style="flex:1; min-width:0;">
      <div class="pd-name" id="pdName">—</div>
      <div class="pd-sub" id="pdSub">—</div>
      <span class="pd-status" id="pdStatus"></span>
    </div>
  </div>

  <div class="pd-rows">
    <div class="pd-row"><span>Quantidade em estoque</span><strong id="pdQty">—</strong></div>
    <div class="pd-row"><span>Quantidade mínima</span><strong id="pdMin">—</strong></div>
    <div class="pd-row"><span>Preço unitário</span><strong id="pdPrice">—</strong></div>
    <div class="pd-row"><span>Valor em estoque</span><strong id="pdValue" class="g">—</strong></div>
    <div class="pd-row"><span>Fornecedor</span><strong id="pdSupplier">—</strong></div>
    <div class="pd-row"><span>Localização</span><strong id="pdLocation">—</strong></div>
    <div class="pd-row"><span>Observação</span><strong id="pdNote">—</strong></div>
  </div>

  <div class="pd-lotes" id="pdLotes" style="display:none;">
    <div class="pd-lotes__title">📅 Lotes / Validades</div>
    <div id="pdLotesList"></div>
  </div>

  <div class="pd-actions">
    <button type="button" class="btn btn-primary" id="pdEdit">✏️ Editar produto</button>
    <button type="button" class="btn pd-del" id="pdDelete" title="Excluir">🗑️</button>
  </div>
</aside>

{{-- ===== MODAL: Novo Produto ===== --}}
<div class="modal fade" id="newProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content np-modal">
      <div class="modal-header np-modal__head">
        <h5 class="modal-title">+ Novo Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('products.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome do produto *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Ex: Disco de freio dianteiro" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">SKU / Código</label>
              <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Ex: BRK-001">
            </div>
            <div class="col-md-6">
              <label class="form-label">Categoria</label>
              <select name="category" class="form-select js-search-create" autocomplete="off">
                <option value="">Selecionar...</option>
                @foreach($allCategories as $cat)
                  <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
                @if(old('category') && !$allCategories->contains(old('category')))
                  <option value="{{ old('category') }}" selected>{{ old('category') }}</option>
                @endif
              </select>
            </div>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-6"><label class="form-label">Quantidade em estoque *</label><input type="number" name="quantity" class="form-control" value="{{ old('quantity', 0) }}" min="0" required></div>
            <div class="col-md-6"><label class="form-label">Quantidade mínima *</label><input type="number" name="minimum_quantity" class="form-control" value="{{ old('minimum_quantity', 5) }}" min="0" required></div>
          </div>
          <div class="mt-3" style="max-width:50%;"><label class="form-label">Preço unitário (R$)</label><input type="number" name="price" class="form-control" value="{{ old('price') }}" step="0.01" min="0" placeholder="0,00"></div>
          <hr class="np-sep">
          <div class="np-section">Informações adicionais</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Fornecedor</label>
              <select name="supplier" class="form-select js-search-create" autocomplete="off">
                <option value="">Nenhum</option>
                @foreach($suppliers as $sup)
                  <option value="{{ $sup }}" {{ old('supplier') == $sup ? 'selected' : '' }}>{{ $sup }}</option>
                @endforeach
                @if(old('supplier') && !$suppliers->contains(old('supplier')))
                  <option value="{{ old('supplier') }}" selected>{{ old('supplier') }}</option>
                @endif
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Localização</label><input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Ex: Prateleira A3"></div>
          </div>
          <div class="mt-3"><label class="form-label">Observação</label><textarea name="note" class="form-control" rows="2" placeholder="Informações adicionais...">{{ old('note') }}</textarea></div>

          <hr class="np-sep">
          <div class="np-section">Lote / Validade (opcional)</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Lote</label><input type="text" name="batch_lote" class="form-control" value="{{ old('batch_lote') }}" placeholder="Ex: L2026-04"></div>
            <div class="col-md-4"><label class="form-label">Validade</label><input type="date" name="batch_expiry" class="form-control" value="{{ old('batch_expiry') }}"></div>
            <div class="col-md-4"><label class="form-label">Qtd. do lote</label><input type="number" name="batch_quantity" class="form-control" min="1" value="{{ old('batch_quantity') }}" placeholder="0"></div>
          </div>
          <small class="text-secondary">Preencha validade e quantidade para registrar um lote — ele aparece em Validades e nos detalhes do produto.</small>
        </div>
        <div class="modal-footer np-modal__foot">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Produto</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===== MODAL: Editar Produto ===== --}}
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content np-modal">
      <div class="modal-header np-modal__head">
        <h5 class="modal-title">✏️ Editar Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="editProductForm" action="#">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome do produto *</label>
            <input type="text" name="name" id="editName" class="form-control" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">SKU / Código</label>
              <input type="text" name="code" id="editCode" class="form-control" placeholder="Ex: BRK-001">
            </div>
            <div class="col-md-6">
              <label class="form-label">Categoria</label>
              <select name="category" id="editCategory" class="form-select js-search-create" autocomplete="off">
                <option value="">Selecionar...</option>
                @foreach($allCategories as $cat)
                  <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-6"><label class="form-label">Quantidade em estoque *</label><input type="number" name="quantity" id="editQty" class="form-control" min="0" required></div>
            <div class="col-md-6"><label class="form-label">Quantidade mínima *</label><input type="number" name="minimum_quantity" id="editMin" class="form-control" min="0" required></div>
          </div>
          <div class="mt-3" style="max-width:50%;"><label class="form-label">Preço unitário (R$)</label><input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0" placeholder="0,00"></div>
          <hr class="np-sep">
          <div class="np-section">Informações adicionais</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Fornecedor</label>
              <select name="supplier" id="editSupplier" class="form-select js-search-create" autocomplete="off">
                <option value="">Nenhum</option>
                @foreach($suppliers as $sup)
                  <option value="{{ $sup }}">{{ $sup }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Localização</label><input type="text" name="location" id="editLocation" class="form-control" placeholder="Ex: Prateleira A3"></div>
          </div>
          <div class="mt-3"><label class="form-label">Observação</label><textarea name="note" id="editNote" class="form-control" rows="2" placeholder="Informações adicionais..."></textarea></div>

          <hr class="np-sep">
          <div class="np-section">➕ Adicionar NOVO lote (opcional)</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Lote</label><input type="text" name="batch_lote" id="editBatchLote" class="form-control" placeholder="Ex: L2026-04"></div>
            <div class="col-md-4"><label class="form-label">Validade</label><input type="date" name="batch_expiry" id="editBatchExpiry" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Qtd. do lote</label><input type="number" name="batch_quantity" id="editBatchQty" class="form-control" min="1" placeholder="0"></div>
          </div>
          <small class="text-secondary">Isto <strong>cria um lote novo</strong>. Para editar ou renomear um lote que já existe, use a tela <strong>Validades</strong> (ícone de lápis na linha do lote).</small>
        </div>
        <div class="modal-footer np-modal__foot">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===== MODAL: Excluir Produto (confirmação) ===== --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content del-modal">
      <div class="del-ico">🗑️</div>
      <h5 class="del-title">Excluir produto?</h5>
      <p class="del-text">Tem certeza que deseja excluir &ldquo;<span id="delName">este produto</span>&rdquo;?<br>Esta ação não pode ser desfeita.</p>
      <div class="del-actions">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form method="POST" id="delForm" action="#" style="margin:0;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn del-confirm">Sim, excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>

@if($errors->any() && old('name') !== null && old('_method') === null)
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('newProductModal');if(m&&window.bootstrap)bootstrap.Modal.getOrCreateInstance(m).show();});</script>
@endif

<style>
    .prod{ color: var(--t-text); }
    .prod-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .prod-title{ margin:0; font-weight:800; }
    .prod-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .prod-actions{ display:flex; gap:8px; flex-wrap:wrap; }

    .prod-kpis{ display:grid; grid-template-columns: repeat(5, 1fr); gap:12px; margin-bottom:16px; }
    .rp-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:16px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; } .rp-card--red::before{ background:#ef4444; }
    .rp-card__label{ color: var(--t-muted); font-size:.8rem; }
    .rp-card__value{ font-size:1.8rem; font-weight:800; margin:6px 0 2px; }
    .rp-card__foot{ color: var(--t-muted); font-size:.76rem; }

    .prod-filters{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:14px; margin-bottom:16px; }
    .rp-search{ position:relative; flex:1; min-width:240px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:42px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-input:focus{ border-color: var(--accent, #3b82f6); }
    .rp-sel{ height:42px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text);  cursor:pointer; }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; overflow:visible; }
    .prod-cat-head{ display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px; border-bottom:1px solid var(--t-border); }
    .prod-cat-title{ font-weight:700; }
    .prod-cat-sub{ color: var(--t-muted); font-size:.82rem; }

    /* botão Colunas + menu */
    .cols-wrap{ position:relative; }
    .cols-menu{ position:absolute; right:0; top:calc(100% + 6px); z-index:50; min-width:190px; background: var(--t-panel); border:1px solid var(--t-input-border); border-radius:12px; padding:10px; box-shadow:0 12px 30px rgba(0,0,0,.45); display:none; }
    .cols-menu.open{ display:block; }
    .cols-menu__title{ font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color: var(--t-muted); margin-bottom:8px; }
    .cols-menu label{ display:flex; align-items:center; gap:8px; padding:5px 4px; font-size:.88rem; cursor:pointer; border-radius:6px; }
    .cols-menu label:hover{ background: rgba(124,92,252,.10); }
    .cols-menu input{ accent-color: var(--accent, #3b82f6); width:15px; height:15px; }

    /* tabela escura */
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; white-space:nowrap; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-table tbody tr:last-child td{ border-bottom:0 !important; }
    .rp-table tbody tr:hover td{ background: rgba(124,92,252,.08) !important; }
    .rp-th{ cursor:pointer; }
    .rp-sort{ color: var(--t-muted) !important; text-decoration:none; white-space:nowrap; }
    .rp-sort:hover{ color: var(--t-text) !important; }
    .rp-sort.active{ color: var(--accent, #3b82f6) !important; font-weight:700; }

    .rp-prod{ display:flex; align-items:center; gap:10px; }
    .rp-ico{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; font-size:15px; flex:0 0 auto; }
    .rp-pname{ font-weight:600; } .rp-psku{ color: var(--t-muted); font-size:.78rem; }
    .rp-cat{ display:inline-block; padding:3px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
    .rp-qty{ display:flex; align-items:center; gap:10px; }
    .rp-bar{ width:64px; height:6px; border-radius:999px; background: var(--t-border); overflow:hidden; flex:0 0 auto; }
    .rp-bar__fill{ display:block; height:100%; border-radius:999px; }
    .rp-qty__n{ font-weight:600; } .rp-money{ color:#22c55e; font-weight:600; }
    .rp-badge{ display:inline-block; padding:3px 10px; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; }
    .rp-badge--green{ background:rgba(34,197,94,.15); color:#22c55e; } .rp-badge--yellow{ background:rgba(245,200,66,.15); color:#ffb700; } .rp-badge--red{ background:rgba(239,68,68,.15); color:#ef4444; }

    .acts-cell{ display:flex; gap:6px; align-items:center; }
    .act-btn{ padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1px solid transparent; display:inline-flex; align-items:center; gap:5px; text-decoration:none; transition:.15s; background:transparent; white-space:nowrap; font-family:inherit; }
    .act-view{ background: var(--t-panel-2); color: var(--t-muted); border:1px solid var(--t-input-border); }
    .act-view:hover{ color: var(--t-text); border-color: var(--accent, #3b82f6); }
    .act-edit{ background: rgba(59,130,246,.15); color:#3b82f6; border:1px solid rgba(59,130,246,.30); }
    .act-edit:hover{ background: rgba(59,130,246,.28); }
    .act-del{ background: rgba(239,68,68,.13); color:#ef4444; border:1px solid rgba(239,68,68,.30); }
    .act-del:hover{ background: rgba(239,68,68,.25); }

    .rp-check{ appearance:none; -webkit-appearance:none; width:18px; height:18px; border-radius:5px; border:1px solid var(--t-input-border); background: var(--t-panel-2) !important; cursor:pointer; position:relative; flex:0 0 auto; vertical-align:middle; margin:0; }
    .rp-check:checked{ background: var(--accent, #3b82f6) !important; border-color: var(--accent, #3b82f6); }
    .rp-check:checked::after{ content:''; position:absolute; left:5px; top:1px; width:5px; height:9px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg); }
    .rp-bulk{ display:flex; align-items:center; gap:12px; padding:10px 16px; background: rgba(59,130,246,.10); border-bottom:1px solid var(--t-border); font-size:.88rem; }
    .rp-bulk strong{ color: var(--accent, #3b82f6); }

    .rp-pagination{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; flex-wrap:wrap; gap:10px; }
    .rp-pag-info{ color: var(--t-muted); font-size:.85rem; }
    .rp-pagination .pagination{ margin:0; }
    .rp-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .rp-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .rp-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }

    /* modal */
    .np-modal{ background: var(--t-panel); color: var(--t-text); border:1px solid var(--t-border); border-radius:16px; }
    .np-modal .form-control, .np-modal .form-select{ background: var(--t-panel-2); color: var(--t-text); border-color: var(--t-input-border); }
    .np-modal .form-control::placeholder, .np-modal textarea::placeholder{ color: var(--t-muted); }
    .np-modal__head, .np-modal__foot{ border-color: var(--t-border); }
    .np-sep{ border-color: var(--t-border); margin:18px 0 12px; }
    .np-section{ text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; color: var(--t-muted); margin-bottom:10px; }
    .np-modal label.form-label{ font-size:.85rem; color: var(--t-muted); }
    /* fundo do modal ACIMA da lateral (z-index 1200) p/ borrar tudo, inclusive a sidebar */
    .modal-backdrop.show{ z-index:1250 !important; background: rgba(8,12,22,.55) !important; opacity:1 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    #newProductModal{ z-index:1260 !important; }
    #newProductModal .modal-dialog{ max-width:520px; }
    #editProductModal{ z-index:1260 !important; }
    #editProductModal .modal-dialog{ max-width:520px; }
    #deleteModal{ z-index:1260 !important; }
    #deleteModal .modal-dialog{ max-width:420px; }

    /* esconder as setinhas dos campos numéricos dentro dos modais */
    .np-modal input[type=number]::-webkit-outer-spin-button,
    .np-modal input[type=number]::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
    .np-modal input[type=number]{ -moz-appearance:textfield; }

    /* modal de exclusão */
    .del-modal{ background: var(--t-panel); border:1px solid rgba(239,68,68,.35); border-radius:18px; padding:30px 26px; text-align:center; color: var(--t-text); }
    .del-ico{ font-size:42px; margin-bottom:10px; }
    .del-title{ font-weight:800; margin:0 0 8px; }
    .del-text{ color: var(--t-muted); font-size:.9rem; margin:0 0 22px; line-height:1.5; }
    .del-actions{ display:flex; gap:10px; justify-content:center; }
    .del-actions form{ margin:0; }
    .del-confirm{ background: rgba(239,68,68,.15); color:#ef4444; border:1px solid rgba(239,68,68,.45); font-weight:600; }
    .del-confirm:hover{ background: rgba(239,68,68,.28); color:#ef4444; }

    @media (max-width:1100px){ .prod-kpis{ grid-template-columns: repeat(2, 1fr); } }

    /* Painel lateral de detalhes */
    .pd-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.5); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px); opacity:0; visibility:hidden; transition:.2s; z-index:1255; }
    .pd-overlay.open{ opacity:1; visibility:visible; }
    .pd-drawer{ position:fixed; top:0; right:0; height:100%; width:420px; max-width:92vw; background:var(--t-panel); color:var(--t-text); border-left:1px solid var(--t-border); transform:translateX(100%); transition:transform .25s ease; z-index:1260; display:flex; flex-direction:column; padding:20px; overflow-y:auto; }
    .pd-drawer.open{ transform:translateX(0); box-shadow:-20px 0 50px rgba(0,0,0,.45); }
    .pd-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .pd-title{ margin:0; font-weight:800; color:#f3f4f6; }
    .pd-close{ background:var(--t-panel-2); border:1px solid var(--t-input-border); color:var(--t-text); width:32px; height:32px; border-radius:8px; cursor:pointer; }
    .pd-close:hover{ border-color:#ef4444; color:#ef4444; }
    .pd-card{ display:flex; gap:12px; align-items:center; background:var(--t-panel-2); border:1px solid var(--t-border); border-radius:14px; padding:16px; margin-bottom:16px; }
    .pd-ico{ width:48px; height:48px; border-radius:12px; background:rgba(124,92,252,.15); color:#a78bfa; display:flex; align-items:center; justify-content:center; font-size:22px; flex:0 0 auto; }
    .pd-name{ font-weight:700; font-size:1.05rem; color:var(--t-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pd-sub{ color:var(--t-muted); font-size:.82rem; margin:2px 0 8px; }
    .pd-status{ font-size:.72rem; font-weight:700; padding:3px 10px; border-radius:999px; }
    .pd-status.s-ok{ background:rgba(34,197,94,.15); color:#22c55e; } .pd-status.s-low{ background:rgba(245,200,66,.15); color:#ffb700; } .pd-status.s-out{ background:rgba(239,68,68,.15); color:#ef4444; }
    .pd-rows{ display:flex; flex-direction:column; }
    .pd-row{ display:flex; justify-content:space-between; gap:10px; padding:12px 2px; border-bottom:1px solid var(--t-border-soft); font-size:.9rem; }
    .pd-row span{ color:var(--t-muted); } .pd-row strong{ text-align:right; color:var(--t-text); } .pd-row strong.g{ color:#22c55e; }
    .pd-lotes{ margin-top:6px; margin-bottom:16px; }
    .pd-lotes__title{ font-weight:700; font-size:.9rem; margin:6px 0 10px; color:#f3f4f6; }
    .pd-lote{ display:flex; justify-content:space-between; align-items:center; gap:10px; background:var(--t-panel-2); border:1px solid var(--t-border); border-radius:10px; padding:10px 12px; margin-bottom:8px; }
    .pd-lote__main{ display:flex; flex-direction:column; min-width:0; }
    .pd-lote__date{ color:var(--t-muted); font-size:.76rem; }
    .pd-lote__right{ display:flex; align-items:center; gap:8px; flex:0 0 auto; }
    .pd-lote__qty{ color:var(--t-text); font-size:.82rem; font-weight:600; }
    .pd-lote__badge{ font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:999px; white-space:nowrap; }
    .pd-actions{ display:flex; gap:8px; margin-top:18px; }
    .pd-actions .btn-primary{ flex:1; }
    .pd-del{ background:rgba(239,68,68,.13); color:#ef4444; border:1px solid rgba(239,68,68,.30); }
    .pd-del:hover{ background:rgba(239,68,68,.25); }
    .act-view svg{ display:block; }
</style>

<script>
  (function(){
    // ===== Botão Colunas (preferência salva no navegador) =====
    var COLS = ['categoria','quantidade','minimo','preco','valor','status','acoes'];
    var KEY = 'stockpro_product_cols';
    var btn = document.getElementById('colsBtn');
    var menu = document.getElementById('colsMenu');

    function apply(hidden){
      COLS.forEach(function(col){
        document.querySelectorAll('.col-' + col).forEach(function(el){
          el.style.display = hidden.indexOf(col) !== -1 ? 'none' : '';
        });
      });
    }
    var hidden = [];
    try { hidden = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch(e){ hidden = []; }
    document.querySelectorAll('.col-toggle').forEach(function(chk){
      if (hidden.indexOf(chk.dataset.col) !== -1) chk.checked = false;
      chk.addEventListener('change', function(){
        if (chk.checked) hidden = hidden.filter(function(c){ return c !== chk.dataset.col; });
        else if (hidden.indexOf(chk.dataset.col) === -1) hidden.push(chk.dataset.col);
        try { localStorage.setItem(KEY, JSON.stringify(hidden)); } catch(e){}
        apply(hidden);
      });
    });
    apply(hidden);

    if (btn) btn.addEventListener('click', function(e){ e.stopPropagation(); menu.classList.toggle('open'); });
    document.addEventListener('click', function(e){ if (menu && !menu.contains(e.target) && e.target !== btn) menu.classList.remove('open'); });

    // ===== Seleção / exportar selecionados =====
    var all = document.querySelector('.rp-check-all');
    var bulk = document.getElementById('rpBulk');
    var count = document.getElementById('rpBulkCount');
    var getRows = function(){ return Array.prototype.slice.call(document.querySelectorAll('.rp-row-check')); };
    function update(){
      var rows = getRows(), checked = rows.filter(function(c){ return c.checked; });
      if (count) count.textContent = checked.length;
      if (bulk) bulk.style.display = checked.length ? 'flex' : 'none';
      if (all) all.checked = checked.length > 0 && checked.length === rows.length;
    }
    if (all) all.addEventListener('change', function(){ getRows().forEach(function(c){ c.checked = all.checked; }); update(); });
    getRows().forEach(function(c){ c.addEventListener('change', update); });
    var exp = document.getElementById('rpBulkExport');
    if (exp) exp.addEventListener('click', function(e){
      e.preventDefault();
      var ids = getRows().filter(function(c){ return c.checked; }).map(function(c){ return c.value; });
      if (!ids.length) return;
      window.location = "{{ route('reports.export') }}?" + ids.map(function(id){ return 'ids[]=' + encodeURIComponent(id); }).join('&');
    });
    var clr = document.getElementById('rpBulkClear');
    if (clr) clr.addEventListener('click', function(){ getRows().forEach(function(c){ c.checked = false; }); if (all) all.checked = false; update(); });
  })();
</script>
<script>
  (function(){
    var drawer = document.getElementById('pdDrawer');
    var overlay = document.getElementById('pdOverlay');
    function fmt(n){ return Number(n).toLocaleString('pt-BR', { minimumFractionDigits:2, maximumFractionDigits:2 }); }
    function openD(){ drawer.classList.add('open'); overlay.classList.add('open'); }
    function closeD(){ drawer.classList.remove('open'); overlay.classList.remove('open'); }

    var closeBtn = document.getElementById('pdClose');
    if (closeBtn) closeBtn.addEventListener('click', closeD);
    if (overlay) overlay.addEventListener('click', closeD);
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeD(); });

    function set(id, val){ var el = document.getElementById(id); if (el) el.textContent = val; }

    document.querySelectorAll('.js-view').forEach(function(btn){
      btn.addEventListener('click', function(){
        fetch(btn.dataset.show, { headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' } })
          .then(function(r){ return r.json(); })
          .then(function(d){
            set('pdName', d.name || '—');
            set('pdSub', (d.code || '—') + (d.category ? ' · ' + d.category : ''));
            var st = document.getElementById('pdStatus');
            if (d.status === 'OK'){ st.className = 'pd-status s-ok'; st.textContent = '✓ OK'; }
            else if (d.status === 'Baixo'){ st.className = 'pd-status s-low'; st.textContent = '⚠ Baixo'; }
            else { st.className = 'pd-status s-out'; st.textContent = '✕ Sem estoque'; }
            set('pdQty', (d.quantity != null ? d.quantity : 0) + ' unidades');
            set('pdMin', (d.minimum_quantity != null ? d.minimum_quantity : 0) + ' unidades');
            set('pdPrice', d.price != null ? 'R$ ' + fmt(d.price) : '—');
            set('pdValue', d.stock_value != null ? 'R$ ' + fmt(d.stock_value) : '—');
            set('pdSupplier', d.supplier || '—');
            set('pdLocation', d.location || '—');
            set('pdNote', d.note || '—');

            // Lotes / Validades
            var lotesBox = document.getElementById('pdLotes');
            var lotesList = document.getElementById('pdLotesList');
            if (lotesBox && lotesList){
              if (d.batches && d.batches.length){
                var stMap = { vencido:['Vencido','#fca5a5'], d7:['Vence em 7 dias','#fca5a5'], d30:['Vence em 30 dias','#fcd34d'], d90:['Vence em 90 dias','#93c5fd'], ok:['No prazo','#86efac'] };
                lotesList.innerHTML = d.batches.map(function(b){
                  var s = stMap[b.status] || ['—','#94a3b8'];
                  return '<div class="pd-lote">'
                       + '<div class="pd-lote__main"><strong>' + (b.lote || 'Sem lote') + '</strong>'
                       + '<span class="pd-lote__date">venc. ' + (b.expiry_date || '—') + '</span></div>'
                       + '<div class="pd-lote__right"><span class="pd-lote__qty">' + b.quantity + ' un.</span>'
                       + '<span class="pd-lote__badge" style="color:' + s[1] + ';background:' + s[1] + '22;">' + s[0] + '</span></div>'
                       + '</div>';
                }).join('');
                lotesBox.style.display = 'block';
              } else {
                lotesBox.style.display = 'none';
              }
            }

            window.spCurrent = d;
            openD();
          })
          .catch(function(){ alert('Não foi possível carregar os detalhes do produto.'); });
      });
    });
  })();
</script>
<script>
  (function(){
    function showModal(id){ var el=document.getElementById(id); if(el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show(); }
    function closeDrawer(){ var dr=document.getElementById('pdDrawer'), ov=document.getElementById('pdOverlay'); if(dr)dr.classList.remove('open'); if(ov)ov.classList.remove('open'); }

    function setSelect(id, val){
      var sel=document.getElementById(id); if(!sel) return; val = val || '';
      // Se o campo virou Tom Select (pesquisável/criável), usa a API dele
      if (sel.tomselect){
        var ts = sel.tomselect;
        if (val){ ts.addOption({ value: val, text: val }); ts.addItem(val, true); }
        else { ts.clear(true); }
        return;
      }
      // select nativo
      if (val && !Array.prototype.some.call(sel.options, function(o){ return o.value === val; })){
        var opt=document.createElement('option'); opt.value=val; opt.textContent=val; sel.appendChild(opt);
      }
      sel.value = val;
    }
    function set(id, val){ var el=document.getElementById(id); if(el) el.value = (val != null ? val : ''); }

    function fillEdit(d){
      if(!d) return;
      set('editName', d.name);
      set('editCode', d.code);
      setSelect('editCategory', d.category);
      set('editQty', d.quantity != null ? d.quantity : 0);
      set('editMin', d.minimum_quantity != null ? d.minimum_quantity : 0);
      set('editPrice', d.price != null ? d.price : '');
      setSelect('editSupplier', d.supplier);
      set('editLocation', d.location);
      set('editNote', d.note);
      set('editBatchLote', ''); set('editBatchExpiry', ''); set('editBatchQty', '');
      var f=document.getElementById('editProductForm'); if(f) f.action = d.update_url;
    }

    // Editar pela linha (busca os dados e abre o modal)
    document.querySelectorAll('.js-edit').forEach(function(btn){
      btn.addEventListener('click', function(){
        fetch(btn.dataset.show, { headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' } })
          .then(function(r){ return r.json(); })
          .then(function(d){ fillEdit(d); showModal('editProductModal'); })
          .catch(function(){ alert('Não foi possível carregar o produto.'); });
      });
    });

    // Editar pelo painel lateral (usa os dados já carregados)
    var pdEdit=document.getElementById('pdEdit');
    if(pdEdit) pdEdit.addEventListener('click', function(){ fillEdit(window.spCurrent); closeDrawer(); showModal('editProductModal'); });

    // Excluir (modal de confirmação)
    function openDelete(name, url){
      var n=document.getElementById('delName'); if(n) n.textContent = name || 'este produto';
      var f=document.getElementById('delForm'); if(f) f.action = url;
      showModal('deleteModal');
    }
    document.querySelectorAll('.js-delete').forEach(function(btn){
      btn.addEventListener('click', function(){ openDelete(btn.dataset.name, btn.dataset.url); });
    });
    var pdDelete=document.getElementById('pdDelete');
    if(pdDelete) pdDelete.addEventListener('click', function(){ if(window.spCurrent){ closeDrawer(); openDelete(window.spCurrent.name, window.spCurrent.delete_url); } });
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
@endsection

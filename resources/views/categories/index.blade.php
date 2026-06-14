@extends('layouts.app')

@section('title', 'Categorias')

@php
    $palette = ['#3b82f6','#7c5cfc','#22c55e','#ffb700','#ef4444','#06b6d4','#ec4899','#f97316','#14b8a6','#a855f7'];
    $icons   = ['📦','🔧','🛞','⚙️','🔩','🛢️','🔌','💡','🧰','🚗','🏍️','🪛','⛓️','🛠️','🔋','📷'];
@endphp

@section('content')
<div class="reports-dark cat">

    {{-- Cabeçalho --}}
    <div class="cat-header">
        <div>
            <h3 class="cat-title">Categorias</h3>
            <p class="cat-sub">Organize as peças da sua loja por tipo (motor, freios, suspensão...)</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="catNewBtn" data-bs-toggle="modal" data-bs-target="#catModal">+ Nova Categoria</button>
    </div>

    {{-- Stat cards --}}
    <div class="cat-stats">
        <div class="rp-card rp-card--blue">
            <div class="rp-card__label">Total de Categorias</div>
            <div class="rp-card__value">{{ $totalCategorias }}</div>
        </div>
        <div class="rp-card rp-card--green">
            <div class="rp-card__label">Categorias Ativas</div>
            <div class="rp-card__value" style="color:#22c55e">{{ $totalAtivas }}</div>
        </div>
        <div class="rp-card rp-card--yellow">
            <div class="rp-card__label">Sem produtos</div>
            <div class="rp-card__value" style="color:#ffb700">{{ $totalCategorias - $totalAtivas }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('categories.index') }}" class="cat-toolbar">
        <div class="rp-search">
            <span class="rp-search__ico">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" class="rp-input" placeholder="Buscar categoria...">
        </div>
        <select name="status" class="rp-sel" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <option value="active"   {{ request('status') === 'active' ? 'selected' : '' }}>Ativas</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inativas</option>
        </select>
        <select name="sort" class="rp-sel" onchange="this.form.submit()">
            <option value="">A–Z</option>
            <option value="za" {{ request('sort') === 'za' ? 'selected' : '' }}>Z–A</option>
        </select>
    </form>

    {{-- Grade de cards --}}
    @if($cats->isEmpty())
        <div class="cat-empty">
            <div class="cat-empty-emoji">🏷️</div>
            <h5>Nenhuma categoria encontrada</h5>
            <p class="cat-sub">Comece com algumas sugestões para peças de moto:</p>
            <div class="cat-suggest">
                @foreach(['Motor','Freios','Suspensão','Transmissão','Elétrica','Pneus','Lubrificantes','Acessórios'] as $sug)
                    <form method="POST" action="{{ route('categories.store') }}">
                        @csrf
                        <input type="hidden" name="name" value="{{ $sug }}">
                        <button type="submit" class="cat-chip">＋ {{ $sug }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    @else
        <div class="cat-grid">
            @foreach($cats as $cat)
                @php $c = $cat->color ?: '#3b82f6'; $ic = $cat->icon ?: '📦'; @endphp
                <div class="cat-card">
                    <span class="cat-card__bar" style="background: {{ $c }};"></span>
                    <div class="cat-ico" style="background: {{ $c }}22; color: {{ $c }};">{{ $ic }}</div>
                    <div class="cat-name">{{ $cat->name }}</div>
                    <div class="cat-desc">{{ $cat->description ?: 'Sem descrição.' }}</div>
                    <div class="cat-meta">
                        <span class="cat-count {{ $cat->is_active ? '' : 'is-zero' }}">{{ $cat->products_count }} produto(s)</span>
                        @if($cat->is_active)
                            <span class="cat-chip-status s-active">Ativa</span>
                        @else
                            <span class="cat-chip-status s-inactive">Inativa</span>
                        @endif
                    </div>
                    <div class="cat-actions">
                        <button type="button" class="act-btn act-edit cat-edit-btn"
                                data-id="{{ $cat->id }}"
                                data-name="{{ e($cat->name) }}"
                                data-description="{{ e($cat->description) }}"
                                data-icon="{{ $cat->icon ?: '📦' }}"
                                data-color="{{ $c }}"
                                data-url="{{ route('categories.update', $cat) }}">✏️ Editar</button>
                        <button type="button" class="act-btn act-del js-delete" data-name="{{ e($cat->name) }}" data-url="{{ route('categories.destroy', $cat) }}" data-kind="categoria">🗑️ Excluir</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid-pagination">
            <div class="grid-paginfo">Mostrando {{ $cats->firstItem() ?? 0 }}–{{ $cats->lastItem() ?? 0 }} de {{ $cats->total() }} categoria(s)</div>
            <div>{{ $cats->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    @endif
</div>

{{-- ===== MODAL: Nova / Editar Categoria ===== --}}
<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cat-modal">
      <div class="modal-header cat-modal__head">
        <h5 class="modal-title" id="catModalTitle">+ Nova Categoria</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('categories.store') }}" id="catForm">
        @csrf
        <input type="hidden" name="_method" id="catMethod" value="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome da categoria *</label>
            <input type="text" name="name" id="catName" class="form-control" maxlength="120" placeholder="Ex: Freios" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="description" id="catDesc" class="form-control" rows="2" maxlength="500" placeholder="Ex: Pastilhas, discos, fluido de freio..."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Ícone</label>
            <div class="icon-grid" id="catIconGrid">
              @foreach($icons as $emoji)
                <button type="button" class="icon-opt" data-icon="{{ $emoji }}">{{ $emoji }}</button>
              @endforeach
            </div>
            <input type="hidden" name="icon" id="catIcon" value="📦">
          </div>

          <div class="mb-2">
            <label class="form-label">Cor</label>
            <div class="color-grid" id="catColorGrid">
              @foreach($palette as $hex)
                <span class="color-opt" data-color="{{ $hex }}" style="background: {{ $hex }};"></span>
              @endforeach
            </div>
            <input type="hidden" name="color" id="catColor" value="#3b82f6">
          </div>

          {{-- Preview --}}
          <div class="cat-preview" id="catPreview">
            <span class="cat-preview__bar" id="pvBar"></span>
            <div class="cat-ico" id="pvIco">📦</div>
            <div class="cat-name" id="pvName">Nome da categoria</div>
            <div class="cat-desc" id="pvDesc">Descrição aparece aqui.</div>
          </div>
        </div>
        <div class="modal-footer cat-modal__foot">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="catSubmit">Salvar Categoria</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($errors->any() && old('name') !== null)
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('catModal');if(m&&window.bootstrap)bootstrap.Modal.getOrCreateInstance(m).show();});</script>
@endif

<style>
    .cat{ color: var(--t-text); }
    .cat-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .cat-title{ margin:0; font-weight:800; } .cat-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }

    .cat-stats{ display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:16px; }
    .rp-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:16px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; }
    .rp-card__label{ color: var(--t-muted); font-size:.8rem; } .rp-card__value{ font-size:1.8rem; font-weight:800; margin-top:6px; }

    .cat-toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:12px; margin-bottom:16px; }
    .rp-search{ position:relative; flex:1; min-width:200px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:40px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-sel{ height:40px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text);  cursor:pointer; }

    .cat-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap:14px; }
    .cat-card{ position:relative; background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; overflow:hidden; transition: transform .12s, border-color .12s, box-shadow .12s; }
    .cat-card:hover{ transform: translateY(-3px); border-color: rgba(124,92,252,.45); box-shadow:0 10px 28px rgba(0,0,0,.35); }
    .cat-card__bar{ position:absolute; top:0; left:0; right:0; height:4px; }
    .cat-ico{ width:50px; height:50px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:24px; margin:6px 0 14px; }
    .cat-name{ font-weight:700; font-size:1.05rem; }
    .cat-desc{ font-size:.78rem; color: var(--t-muted); line-height:1.5; margin:4px 0 14px; min-height:34px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .cat-meta{ display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .cat-count{ font-weight:700; font-size:.9rem; } .cat-count.is-zero{ color: var(--t-muted); }
    .cat-chip-status{ font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .s-active{ background:rgba(34,197,94,.15); color:#22c55e; border:1px solid rgba(34,197,94,.25); }
    .s-inactive{ background:rgba(148,163,184,.12); color:var(--t-muted); border:1px solid var(--t-border); }
    .cat-actions{ display:flex; gap:6px; }

    .act-btn{ padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1px solid transparent; display:inline-flex; align-items:center; gap:5px; text-decoration:none; transition:.15s; background:transparent; font-family:inherit; }
    .act-edit{ background: rgba(59,130,246,.15); color:#3b82f6; border:1px solid rgba(59,130,246,.30); } .act-edit:hover{ background: rgba(59,130,246,.28); }
    .act-del{ background: rgba(239,68,68,.13); color:#ef4444; border:1px solid rgba(239,68,68,.30); } .act-del:hover{ background: rgba(239,68,68,.25); }

    .cat-pageinfo{ color: var(--t-muted); font-size:.85rem; margin-top:16px; }

    .cat-empty{ text-align:center; padding:40px 16px; background: var(--t-panel); border:1px dashed var(--t-input-border); border-radius:16px; }
    .cat-empty-emoji{ font-size:40px; } .cat-empty h5{ margin:8px 0 2px; }
    .cat-suggest{ display:flex; gap:8px; flex-wrap:wrap; justify-content:center; margin-top:14px; }
    .cat-suggest form{ margin:0; }
    .cat-chip{ background: var(--t-panel-2); color: var(--t-text); border:1px solid var(--t-input-border); border-radius:999px; padding:6px 14px; font-size:.85rem; cursor:pointer; }
    .cat-chip:hover{ background: rgba(124,92,252,.15); border-color: rgba(124,92,252,.5); }

    /* modal */
    .cat-modal{ background: var(--t-panel); color: var(--t-text); border:1px solid var(--t-border); border-radius:16px; }
    .cat-modal .form-control{ background: var(--t-panel-2); color: var(--t-text); border-color: var(--t-input-border); }
    .cat-modal .form-control::placeholder{ color: var(--t-muted); }
    .cat-modal__head, .cat-modal__foot{ border-color: var(--t-border); }
    .cat-modal label.form-label{ font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; color: var(--t-muted); }
    .icon-grid{ display:flex; gap:6px; flex-wrap:wrap; background: var(--t-panel-2); border:1px solid var(--t-border); border-radius:10px; padding:8px; }
    .icon-opt{ width:34px; height:34px; border-radius:8px; border:2px solid transparent; background: transparent; font-size:17px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .icon-opt:hover{ background: rgba(124,92,252,.12); } .icon-opt.selected{ border-color: var(--accent, #3b82f6); background: rgba(59,130,246,.15); }
    .color-grid{ display:flex; gap:8px; flex-wrap:wrap; background: var(--t-panel-2); border:1px solid var(--t-border); border-radius:10px; padding:10px; }
    .color-opt{ width:26px; height:26px; border-radius:8px; cursor:pointer; border:3px solid transparent; transition:.15s; }
    .color-opt:hover{ transform: scale(1.15); } .color-opt.selected{ border-color:var(--t-text); box-shadow:0 0 0 2px var(--t-border); }
    .cat-preview{ position:relative; margin-top:16px; background: var(--t-panel-2); border:1px solid var(--t-border); border-radius:14px; padding:16px; overflow:hidden; }
    .cat-preview__bar{ position:absolute; top:0; left:0; right:0; height:4px; background: #3b82f6; }
    .modal-backdrop.show{ z-index:1250 !important; background: rgba(8,12,22,.55) !important; opacity:1 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    #catModal{ z-index:1260 !important; }

    @media (max-width: 900px){ .cat-stats{ grid-template-columns: 1fr; } }

    .grid-pagination{ display:flex; align-items:center; justify-content:space-between; margin-top:18px; flex-wrap:wrap; gap:10px; }
    .grid-paginfo{ color: var(--t-muted); font-size:.85rem; }
    .grid-pagination .pagination{ margin:0; }
    .grid-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .grid-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .grid-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }
</style>

<script>
  (function(){
    var form = document.getElementById('catForm');
    var title = document.getElementById('catModalTitle');
    var method = document.getElementById('catMethod');
    var submit = document.getElementById('catSubmit');
    var fName = document.getElementById('catName');
    var fDesc = document.getElementById('catDesc');
    var fIcon = document.getElementById('catIcon');
    var fColor = document.getElementById('catColor');
    var storeUrl = "{{ route('categories.store') }}";

    var pvBar = document.getElementById('pvBar'), pvIco = document.getElementById('pvIco');
    var pvName = document.getElementById('pvName'), pvDesc = document.getElementById('pvDesc');

    function selectIcon(v){
      fIcon.value = v;
      document.querySelectorAll('.icon-opt').forEach(function(b){ b.classList.toggle('selected', b.dataset.icon === v); });
      preview();
    }
    function selectColor(v){
      fColor.value = v;
      document.querySelectorAll('.color-opt').forEach(function(b){ b.classList.toggle('selected', b.dataset.color === v); });
      preview();
    }
    function preview(){
      pvBar.style.background = fColor.value;
      pvIco.textContent = fIcon.value;
      pvIco.style.background = fColor.value + '22';
      pvIco.style.color = fColor.value;
      pvName.textContent = fName.value || 'Nome da categoria';
      pvDesc.textContent = fDesc.value || 'Descrição aparece aqui.';
    }

    document.querySelectorAll('.icon-opt').forEach(function(b){ b.addEventListener('click', function(){ selectIcon(b.dataset.icon); }); });
    document.querySelectorAll('.color-opt').forEach(function(b){ b.addEventListener('click', function(){ selectColor(b.dataset.color); }); });
    fName.addEventListener('input', preview);
    fDesc.addEventListener('input', preview);

    // Abrir em modo NOVO
    var newBtn = document.getElementById('catNewBtn');
    if (newBtn) newBtn.addEventListener('click', function(){
      form.action = storeUrl; method.value = '';
      title.textContent = '+ Nova Categoria'; submit.textContent = 'Salvar Categoria';
      fName.value = ''; fDesc.value = '';
      selectIcon('📦'); selectColor('#3b82f6');
    });

    // Abrir em modo EDITAR
    document.querySelectorAll('.cat-edit-btn').forEach(function(btn){
      btn.addEventListener('click', function(){
        form.action = btn.dataset.url; method.value = 'PUT';
        title.textContent = '✏️ Editar Categoria'; submit.textContent = 'Salvar alterações';
        fName.value = btn.dataset.name || '';
        fDesc.value = btn.dataset.description || '';
        selectIcon(btn.dataset.icon || '📦');
        selectColor(btn.dataset.color || '#3b82f6');
        if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById('catModal')).show();
      });
    });

    selectIcon('📦'); selectColor('#3b82f6');
  })();
</script>

<script>
(function(){
  var input = document.querySelector('input[name="search"]');
  var grid = document.querySelector('.cat-grid');
  if(!input || !grid) return;
  function norm(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
  var cards = grid.querySelectorAll('.cat-card');
  input.addEventListener('input', function(){
    var q = norm(input.value.trim());
    Array.prototype.forEach.call(cards, function(c){
      var match = norm(c.textContent).indexOf(q) !== -1;
      c.style.display = (q === '' || match) ? '' : 'none';
    });
  });
})();
</script>

{{-- ===== MODAL: confirmar exclusão ===== --}}
<div class="del-overlay" id="delOverlay">
  <div class="del-box">
    <div class="del-ico">🗑️</div>
    <h5 class="del-title" id="delTitle">Excluir?</h5>
    <p class="del-text" id="delText">Tem certeza? Esta ação não pode ser desfeita.</p>
    <div class="del-actions">
      <button type="button" class="btn btn-secondary" id="delCancel">Cancelar</button>
      <form method="POST" id="delForm" action="#" style="margin:0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn del-confirm">Sim, excluir</button>
      </form>
    </div>
  </div>
</div>

<style>
  .del-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.55); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:2000; }
  .del-overlay.open{ display:flex; }
  .del-box{ background:var(--t-panel-2); border:1px solid rgba(239,68,68,.42); border-radius:18px; padding:30px 26px; max-width:420px; width:92%; text-align:center; color:var(--t-text); box-shadow:0 30px 60px rgba(0,0,0,.5); }
  .del-ico{ font-size:40px; margin-bottom:10px; }
  .del-title{ font-weight:800; margin:0 0 8px; }
  .del-text{ color:var(--t-muted); font-size:.9rem; margin:0 0 22px; line-height:1.5; }
  .del-actions{ display:flex; gap:10px; justify-content:center; }
  .del-actions form{ margin:0; }
  .del-confirm{ background:rgba(239,68,68,.15); color:#ef4444; border:1px solid rgba(239,68,68,.45); font-weight:600; }
  .del-confirm:hover{ background:rgba(239,68,68,.28); color:#ef4444; }
</style>

<script>
(function(){
  var overlay = document.getElementById('delOverlay');
  var form = document.getElementById('delForm');
  var title = document.getElementById('delTitle');
  var text = document.getElementById('delText');
  var cancel = document.getElementById('delCancel');
  function closeDel(){ if (overlay) overlay.classList.remove('open'); }
  if (cancel) cancel.addEventListener('click', closeDel);
  if (overlay) overlay.addEventListener('click', function(e){ if (e.target === overlay) closeDel(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeDel(); });
  document.querySelectorAll('.js-delete').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (form) form.action = btn.dataset.url;
      var kind = btn.dataset.kind || 'item';
      if (title) title.textContent = 'Excluir ' + kind + '?';
      if (text) text.innerHTML = 'Tem certeza que deseja excluir &ldquo;' + (btn.dataset.name || '') + '&rdquo;?<br>Esta ação não pode ser desfeita.';
      if (overlay) overlay.classList.add('open');
    });
  });
})();
</script>
@endsection

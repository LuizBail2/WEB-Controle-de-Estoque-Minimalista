@extends('layouts.app')

@section('title', 'Fornecedores')

@php
    $palette = ['#3b82f6','#7c5cfc','#22c55e','#ffb700','#ef4444','#06b6d4','#ec4899','#f97316','#14b8a6','#a855f7'];
    $sColor = fn ($name) => $palette[abs(crc32((string) $name)) % count($palette)];
@endphp

@section('content')
<div class="reports-dark sup">

    {{-- Cabeçalho --}}
    <div class="sup-header">
        <div>
            <h3 class="sup-title">Fornecedores</h3>
            <p class="sup-sub">Cadastre quem fornece as peças do seu estoque</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="supNewBtn" data-bs-toggle="modal" data-bs-target="#supModal">+ Novo Fornecedor</button>
    </div>

    {{-- Stat cards --}}
    <div class="sup-stats">
        <div class="rp-card rp-card--blue">
            <div class="rp-card__label">Total de Fornecedores</div>
            <div class="rp-card__value">{{ $totalFornecedores }}</div>
        </div>
        <div class="rp-card rp-card--green">
            <div class="rp-card__label">Com produtos</div>
            <div class="rp-card__value" style="color:#22c55e">{{ $comProdutos }}</div>
        </div>
        <div class="rp-card rp-card--yellow">
            <div class="rp-card__label">Sem produtos</div>
            <div class="rp-card__value" style="color:#ffb700">{{ $totalFornecedores - $comProdutos }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('suppliers.index') }}" class="sup-toolbar">
        <div class="rp-search">
            <span class="rp-search__ico">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" class="rp-input" placeholder="Buscar por nome, contato, e-mail ou telefone...">
        </div>
        <select name="sort" class="rp-sel" onchange="this.form.submit()">
            <option value="">A–Z</option>
            <option value="za" {{ request('sort') === 'za' ? 'selected' : '' }}>Z–A</option>
        </select>
    </form>

    {{-- Grade --}}
    @if($list->isEmpty())
        <div class="sup-empty">
            <div class="sup-empty-emoji">🏢</div>
            <h5>Nenhum fornecedor encontrado</h5>
            <p class="sup-sub">Cadastre seu primeiro fornecedor para vincular aos produtos.</p>
            <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#supModal" id="supEmptyBtn">+ Cadastrar fornecedor</button>
        </div>
    @else
        @php
            $fmtDoc = function ($v) {
                $d = preg_replace('/\D/', '', (string) $v);
                if ($d === '') return $v;
                if (strlen($d) <= 11) return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{1,2}).*$/', '$1.$2.$3-$4', $d);
                return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2}).*$/', '$1.$2.$3/$4-$5', $d);
            };
            $fmtPhone = function ($v) {
                $d = preg_replace('/\D/', '', (string) $v);
                if ($d === '') return $v;
                if (strlen($d) <= 10) return preg_replace('/^(\d{2})(\d{4})(\d{0,4}).*$/', '($1) $2-$3', $d);
                return preg_replace('/^(\d{2})(\d{5})(\d{4}).*$/', '($1) $2-$3', $d);
            };
        @endphp
        <div class="sup-grid">
            @foreach($list as $s)
                @php $c = $sColor($s->name); @endphp
                <div class="sup-card">
                    <span class="sup-card__bar" style="background: {{ $c }};"></span>
                    <div class="sup-card__top">
                        <div class="sup-ico" style="background: {{ $c }}22; color: {{ $c }};">🏢</div>
                        @if($s->is_active)
                            <span class="sup-chip s-active">{{ $s->products_count }} produto(s)</span>
                        @else
                            <span class="sup-chip s-inactive">Sem produtos</span>
                        @endif
                    </div>
                    <div class="sup-name">{{ $s->name }}</div>
                    @if($s->contact_name)<div class="sup-contact">👤 {{ $s->contact_name }}</div>@endif

                    <div class="sup-lines">
                        @if($s->phone)<div class="sup-line">📞 {{ $fmtPhone($s->phone) }}</div>@endif
                        @if($s->email)<div class="sup-line">✉️ {{ $s->email }}</div>@endif
                        @if($s->document)<div class="sup-line">🧾 {{ $fmtDoc($s->document) }}</div>@endif
                        @if($s->address)<div class="sup-line">📍 {{ $s->address }}</div>@endif
                        @if(!$s->phone && !$s->email && !$s->document && !$s->address)
                            <div class="sup-line" style="opacity:.6">Sem dados de contato.</div>
                        @endif
                    </div>

                    <div class="sup-actions">
                        <button type="button" class="act-btn act-edit sup-edit-btn"
                                data-name="{{ e($s->name) }}"
                                data-contact_name="{{ e($s->contact_name) }}"
                                data-phone="{{ e($s->phone) }}"
                                data-email="{{ e($s->email) }}"
                                data-document="{{ e($s->document) }}"
                                data-address="{{ e($s->address) }}"
                                data-note="{{ e($s->note) }}"
                                data-url="{{ route('suppliers.update', $s) }}">✏️ Editar</button>
                        <button type="button" class="act-btn act-del js-delete" data-name="{{ e($s->name) }}" data-url="{{ route('suppliers.destroy', $s) }}" data-kind="fornecedor">🗑️ Excluir</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid-pagination">
            <div class="grid-paginfo">Mostrando {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} de {{ $list->total() }} fornecedor(es)</div>
            <div>{{ $list->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        </div>
    @endif
</div>

{{-- ===== MODAL: Novo / Editar Fornecedor ===== --}}
<div class="modal fade" id="supModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content sup-modal">
      <div class="modal-header sup-modal__head">
        <h5 class="modal-title" id="supModalTitle">+ Novo Fornecedor</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('suppliers.store') }}" id="supForm">
        @csrf
        <input type="hidden" name="_method" id="supMethod" value="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome do fornecedor *</label>
            <input type="text" name="name" id="supName" class="form-control" maxlength="200" placeholder="Ex: MotoPeças Brasil" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Pessoa de contato</label><input type="text" name="contact_name" id="supContact" class="form-control" maxlength="200" placeholder="Ex: João"></div>
            <div class="col-md-6"><label class="form-label">Telefone / WhatsApp</label><input type="text" name="phone" id="supPhone" class="form-control" maxlength="50" placeholder="(42) 99999-9999"></div>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" id="supEmail" class="form-control" maxlength="200" placeholder="contato@fornecedor.com"></div>
            <div class="col-md-6"><label class="form-label">CNPJ / CPF</label><input type="text" name="document" id="supDoc" class="form-control" maxlength="50" placeholder="00.000.000/0000-00"></div>
          </div>
          <div class="mt-3"><label class="form-label">Endereço</label><input type="text" name="address" id="supAddr" class="form-control" maxlength="300" placeholder="Rua, número, cidade..."></div>
          <div class="mt-3"><label class="form-label">Observação</label><textarea name="note" id="supNote" class="form-control" rows="2" maxlength="500" placeholder="Prazo de entrega, condições de pagamento..."></textarea></div>
        </div>
        <div class="modal-footer sup-modal__foot">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="supSubmit">Salvar Fornecedor</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($errors->any() && old('name') !== null)
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('supModal');if(m&&window.bootstrap)bootstrap.Modal.getOrCreateInstance(m).show();});</script>
@endif

<style>
    .sup{ color: var(--t-text); }
    .sup-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .sup-title{ margin:0; font-weight:800; } .sup-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }

    .sup-stats{ display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:16px; }
    .rp-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:16px; position:relative; overflow:hidden; }
    .rp-card::before{ content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--accent, #3b82f6); }
    .rp-card--green::before{ background:#22c55e; } .rp-card--yellow::before{ background:#ffb700; }
    .rp-card__label{ color: var(--t-muted); font-size:.8rem; } .rp-card__value{ font-size:1.8rem; font-weight:800; margin-top:6px; }

    .sup-toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; background: var(--t-panel); border:1px solid var(--t-border); border-radius:14px; padding:12px; margin-bottom:16px; }
    .rp-search{ position:relative; flex:1; min-width:200px; }
    .rp-search__ico{ position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none; opacity:.85; }
    .rp-input{ width:100%; height:40px; padding:0 12px 0 36px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .rp-input::placeholder{ color: var(--t-muted); }
    .rp-sel{ height:40px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text);  cursor:pointer; }

    .sup-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:14px; }
    .sup-card{ position:relative; background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; overflow:hidden; transition: transform .12s, border-color .12s, box-shadow .12s; }
    .sup-card:hover{ transform: translateY(-3px); border-color: rgba(124,92,252,.45); box-shadow:0 10px 28px rgba(0,0,0,.35); }
    .sup-card__bar{ position:absolute; top:0; left:0; right:0; height:4px; }
    .sup-card__top{ display:flex; align-items:center; justify-content:space-between; margin:6px 0 12px; }
    .sup-ico{ width:48px; height:48px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:22px; }
    .sup-name{ font-weight:700; font-size:1.05rem; }
    .sup-contact{ color: var(--t-muted); font-size:.82rem; margin-top:2px; }
    .sup-lines{ margin:12px 0 14px; display:flex; flex-direction:column; gap:5px; }
    .sup-line{ font-size:.82rem; color: var(--t-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sup-chip{ font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .s-active{ background:rgba(34,197,94,.15); color:#22c55e; border:1px solid rgba(34,197,94,.25); }
    .s-inactive{ background:rgba(148,163,184,.12); color:var(--t-muted); border:1px solid var(--t-border); }
    .sup-actions{ display:flex; gap:6px; }

    .act-btn{ padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1px solid transparent; display:inline-flex; align-items:center; gap:5px; text-decoration:none; transition:.15s; background:transparent; font-family:inherit; }
    .act-edit{ background: rgba(59,130,246,.15); color:#3b82f6; border:1px solid rgba(59,130,246,.30); } .act-edit:hover{ background: rgba(59,130,246,.28); }
    .act-del{ background: rgba(239,68,68,.13); color:#ef4444; border:1px solid rgba(239,68,68,.30); } .act-del:hover{ background: rgba(239,68,68,.25); }

    .sup-pageinfo{ color: var(--t-muted); font-size:.85rem; margin-top:16px; }
    .sup-empty{ text-align:center; padding:40px 16px; background: var(--t-panel); border:1px dashed var(--t-input-border); border-radius:16px; }
    .sup-empty-emoji{ font-size:40px; } .sup-empty h5{ margin:8px 0 2px; }

    .sup-modal{ background: var(--t-panel); color: var(--t-text); border:1px solid var(--t-border); border-radius:16px; }
    .sup-modal .form-control{ background: var(--t-panel-2); color: var(--t-text); border-color: var(--t-input-border); }
    .sup-modal .form-control::placeholder, .sup-modal textarea::placeholder{ color: var(--t-muted); }
    .sup-modal__head, .sup-modal__foot{ border-color: var(--t-border); }
    .sup-modal label.form-label{ font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; color: var(--t-muted); }
    .modal-backdrop.show{ z-index:1250 !important; background: rgba(8,12,22,.55) !important; opacity:1 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    #supModal{ z-index:1260 !important; }

    @media (max-width: 900px){ .sup-stats{ grid-template-columns: 1fr; } }

    .grid-pagination{ display:flex; align-items:center; justify-content:space-between; margin-top:18px; flex-wrap:wrap; gap:10px; }
    .grid-paginfo{ color: var(--t-muted); font-size:.85rem; }
    .grid-pagination .pagination{ margin:0; }
    .grid-pagination .page-link{ background: var(--t-panel-2); border-color: var(--t-border); color: var(--t-text); }
    .grid-pagination .page-item.active .page-link{ background: var(--accent, #3b82f6); border-color: var(--accent, #3b82f6); color:#fff; }
    .grid-pagination .page-item.disabled .page-link{ background: transparent; color: var(--t-muted); }
</style>

<script>
  (function(){
    var form = document.getElementById('supForm');
    var title = document.getElementById('supModalTitle');
    var method = document.getElementById('supMethod');
    var submit = document.getElementById('supSubmit');
    var storeUrl = "{{ route('suppliers.store') }}";
    var fields = ['name','contact','phone','email','doc','addr','note'];
    var map = { name:'supName', contact:'supContact', phone:'supPhone', email:'supEmail', doc:'supDoc', addr:'supAddr', note:'supNote' };

    /* ---- Máscaras: CPF/CNPJ e telefone ---- */
    function onlyDigits(v){ return (v || '').replace(/\D/g, ''); }
    window.spMaskDoc = function(v){
      var d = onlyDigits(v).slice(0, 14), o = '';
      if (d.length <= 11){ // CPF 000.000.000-00
        for (var i=0;i<d.length;i++){ if(i===3||i===6)o+='.'; if(i===9)o+='-'; o+=d[i]; }
      } else { // CNPJ 00.000.000/0000-00
        for (var j=0;j<d.length;j++){ if(j===2||j===5)o+='.'; if(j===8)o+='/'; if(j===12)o+='-'; o+=d[j]; }
      }
      return o;
    };
    window.spMaskPhone = function(v){
      var d = onlyDigits(v).slice(0, 11), o = '';
      for (var i=0;i<d.length;i++){
        if (i===0) o+='(';
        if (i===2) o+=') ';
        if ((d.length>10 && i===7) || (d.length<=10 && i===6)) o+='-';
        o+=d[i];
      }
      return o;
    };
    var docEl = document.getElementById('supDoc');
    var phoneEl = document.getElementById('supPhone');
    if (docEl)   docEl.addEventListener('input', function(){ this.value = spMaskDoc(this.value); });
    if (phoneEl) phoneEl.addEventListener('input', function(){ this.value = spMaskPhone(this.value); });

    function clearForm(){ for (var k in map){ var el = document.getElementById(map[k]); if (el) el.value = ''; } }

    var newBtn = document.getElementById('supNewBtn');
    function setNew(){
      form.action = storeUrl; method.value = '';
      title.textContent = '+ Novo Fornecedor'; submit.textContent = 'Salvar Fornecedor';
      clearForm();
    }
    if (newBtn) newBtn.addEventListener('click', setNew);
    var emptyBtn = document.getElementById('supEmptyBtn');
    if (emptyBtn) emptyBtn.addEventListener('click', setNew);

    document.querySelectorAll('.sup-edit-btn').forEach(function(btn){
      btn.addEventListener('click', function(){
        form.action = btn.dataset.url; method.value = 'PUT';
        title.textContent = '✏️ Editar Fornecedor'; submit.textContent = 'Salvar alterações';
        document.getElementById('supName').value    = btn.dataset.name || '';
        document.getElementById('supContact').value = btn.dataset.contact_name || '';
        document.getElementById('supPhone').value   = window.spMaskPhone ? spMaskPhone(btn.dataset.phone || '') : (btn.dataset.phone || '');
        document.getElementById('supEmail').value   = btn.dataset.email || '';
        document.getElementById('supDoc').value     = window.spMaskDoc ? spMaskDoc(btn.dataset.document || '') : (btn.dataset.document || '');
        document.getElementById('supAddr').value    = btn.dataset.address || '';
        document.getElementById('supNote').value    = btn.dataset.note || '';
        if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById('supModal')).show();
      });
    });
  })();
</script>

<script>
(function(){
  var input = document.querySelector('input[name="search"]');
  var grid = document.querySelector('.sup-grid');
  if(!input || !grid) return;
  function norm(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
  var cards = grid.querySelectorAll('.sup-card');
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

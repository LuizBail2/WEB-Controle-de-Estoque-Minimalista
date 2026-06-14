<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:20px;height:20px;object-fit:contain;">
    </div>
    <div class="logo-text">Nexo <span>Estoque</span></div>
  </div>

  @php($u = auth()->user())

  <div class="sidebar-section">Principal</div>

  @if($u->hasPermission('dashboard'))
  <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
    <span class="icon">📊</span> Dashboard
  </a>
  @endif

  @if($u->hasPermission('products'))
  <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
    <span class="icon">📦</span> Produtos
    @php($lowStock = \App\Models\Product::whereColumn('quantity', '<=', 'minimum_quantity')->count())
    @if($lowStock > 0)
      <span class="nav-badge nav-badge-red">{{ $lowStock }}</span>
    @endif
  </a>
  @endif

  @if($u->hasPermission('movements'))
  <a class="nav-item {{ request()->routeIs('movements.*') ? 'active' : '' }}" href="{{ route('movements.index') }}">
    <span class="icon">🔄</span> Movimentações
  </a>
  @endif

  @if($u->hasPermission('purchase_orders'))
  <a class="nav-item {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
    <span class="icon">🧾</span> Pedidos de Compra
  </a>
  @endif

  @if($u->hasPermission('categories'))
  <a class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
    <span class="icon">🏷️</span> Categorias
  </a>
  @endif

  @if($u->hasPermission('suppliers'))
  <a class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
    <span class="icon">🏢</span> Fornecedores
  </a>
  @endif

  @if($u->hasPermission('reports') || $u->hasPermission('finance') || $u->hasPermission('validades'))
  <div class="sidebar-section">Relatórios</div>
  @endif

  @if($u->hasPermission('reports'))
  <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
    <span class="icon">📈</span> Relatórios
  </a>
  @endif

  @if($u->hasPermission('finance'))
  <a class="nav-item {{ request()->routeIs('finance.*') ? 'active' : '' }}" href="{{ route('finance.index') }}">
    <span class="icon">💰</span> Financeiro
  </a>
  @endif

  @if($u->hasPermission('validades'))
  <a class="nav-item {{ request()->routeIs('batches.*') ? 'active' : '' }}" href="{{ route('batches.index') }}">
    <span class="icon">📅</span> Validades
    @php($expCount = \App\Models\Batch::vencidos()->count() + \App\Models\Batch::venceEm(7)->count())
    @if($expCount > 0)
      <span class="nav-badge nav-badge-yellow">{{ $expCount }}</span>
    @endif
  </a>
  @endif

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</div>
      <div style="flex:1">
        <div class="user-name">{{ auth()->user()->name ?? 'Usuário' }}</div>
        <div class="user-role">Minha conta</div>
      </div>
      <a href="{{ route('profile.edit') }}" title="Editar perfil" style="position:relative;color:var(--t-muted);font-size:16px;text-decoration:none;">⚙️
        @php($unreadActs = \App\Models\ActivityLog::where('owner_id', $u->id)->whereNull('read_at')->count())
        @if($unreadActs > 0)
          <span class="sb-notif-dot">{{ $unreadActs > 9 ? '9+' : $unreadActs }}</span>
        @endif
      </a>
    </div>

    <button type="button" id="themeToggle" class="theme-toggle" onclick="toggleTheme()" title="Alternar tema claro/escuro">
      <span class="theme-toggle__ico">🌙</span>
      <span class="theme-toggle__txt">Tema escuro</span>
    </button>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px;">
      @csrf
      <button type="submit" class="btn-logout">Sair</button>
    </form>
  </div>
</aside>

<style>
  .theme-toggle{
    width:100%; margin-top:10px;
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:10px;
    background:var(--t-hover); color:var(--t-text-soft);
    border:1px solid var(--t-sidebar-border);
    font-size:13px; font-weight:700; cursor:pointer;
    transition:.2s;
  }
  .theme-toggle:hover{ border-color:var(--accent,#3b82f6); color:var(--t-text); }
  .theme-toggle__ico{ font-size:15px; }
</style>

<script>
  function applyThemeLabel(theme){
    var t = document.getElementById('themeToggle');
    if(!t) return;
    var ico = t.querySelector('.theme-toggle__ico');
    var txt = t.querySelector('.theme-toggle__txt');
    if(theme === 'light'){ ico.textContent = '☀️'; txt.textContent = 'Tema claro'; }
    else { ico.textContent = '🌙'; txt.textContent = 'Tema escuro'; }
  }

  function toggleTheme(){
    var cur = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
    var next = cur === 'light' ? 'dark' : 'light';
    // troca instantânea
    document.documentElement.dataset.theme = next;
    applyThemeLabel(next);
    // salva na conta (banco) sem recarregar
    fetch("{{ route('profile.theme') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ theme: next })
    }).catch(function(){ /* se falhar, fica trocado só nesta tela */ });
  }

  // ajusta o rótulo ao carregar conforme o tema atual
  applyThemeLabel(document.documentElement.dataset.theme === 'light' ? 'light' : 'dark');
</script>

<style>
  .nav-badge{ margin-left:auto; min-width:20px; height:20px; padding:0 6px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; border-radius:999px; color:#fff; }
  .nav-badge-red{ background: var(--red, #ef4444); }
  .nav-badge-yellow{ background: var(--yellow, #ffb700); color:#1a1a1a; }
  .sb-notif-dot{ position:absolute; top:-6px; right:-9px; background:#ef4444; color:#fff; font-size:9px; font-weight:700; line-height:1; padding:3px 5px; border-radius:999px; }
  .nav-item.nav-soon{ opacity:.45; cursor:not-allowed; }
  .nav-item.nav-soon:hover{ background:transparent; }
</style>

{{-- ===== Responsivo (mobile) + barra de carregamento — injetado pela sidebar (vale em todas as telas) ===== --}}
<div class="sp-loadbar" id="spLoadbar"></div>

<style>
  /* ---------- Barra de carregamento ---------- */
  .sp-loadbar{ position:fixed; top:0; left:0; height:3px; width:0; background:linear-gradient(90deg,#3b82f6,#7c5cfc); z-index:3000; opacity:0; transition:width .2s ease, opacity .3s ease; box-shadow:0 0 10px rgba(59,130,246,.6); }
  .sp-loadbar.sp-active{ opacity:1; }

  /* ---------- Spinner (setas + / -) em TODOS os campos de número ---------- */
  input[type=number]::-webkit-outer-spin-button,
  input[type=number]::-webkit-inner-spin-button{ -webkit-appearance:auto !important; opacity:1 !important; margin:0; }
  input[type=number]{ -moz-appearance:auto !important; appearance:auto; }

  /* ---------- Botão hambúrguer + overlay ---------- */
  .sp-hamb{ display:none; position:fixed; top:12px; left:12px; z-index:1310; width:42px; height:42px; border-radius:10px; background:var(--t-panel-2); color:var(--t-text); border:1px solid var(--t-input-border); font-size:20px; align-items:center; justify-content:center; cursor:pointer; }
  .sp-ovl{ position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1290; display:none; }
  .sp-ovl.sp-open{ display:block; }

  /* ---------- Layout mobile ---------- */
  @media (max-width: 992px){
    .sp-hamb{ display:flex; }
    .sidebar{ position:fixed !important; left:0; top:0; height:100vh; transform:translateX(-100%); transition:transform .25s ease; z-index:1300; }
    .sidebar.sp-open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,.6); }
    .main{ margin-left:0 !important; width:100% !important; max-width:100vw !important; overflow-x:hidden !important; padding:62px 12px 22px !important; }

    /* Empilha TODOS os grids de KPIs/estatísticas e blocos de 2 colunas */
    .prod-kpis, .dash-kpis, .rp-kpis, .cat-stats, .sup-stats{ grid-template-columns: repeat(2, 1fr) !important; }
    .dash-grid, .rp-charts, .rp-chart-grid{ grid-template-columns: 1fr !important; }

    /* Cabeçalhos e toolbars quebram linha */
    .prod-header, .dash-header, .rp-header, .cat-header, .sup-header, .po-header,
    .prod-filters, .cat-toolbar, .sup-toolbar, .po-toolbar, .rp-toolbar, .dash-actions, .po-actions{
      flex-wrap: wrap !important;
    }

    /* Campo de busca ocupa a linha inteira */
    .prod-filters .rp-search, .cat-toolbar .rp-search, .sup-toolbar .rp-search,
    .po-toolbar .rp-search, .rp-toolbar .rp-search, .prod-search, .dash-cat-head{
      flex: 1 1 100% !important; min-width: 0 !important;
    }

    /* Tabelas rolam na horizontal dentro do cartão */
    .table-responsive{ overflow-x:auto !important; -webkit-overflow-scrolling:touch; max-width:100%; }
    .sp-table, .rp-table:not(.sp-cards){ min-width: 640px; }

    /* Grades de cards (categorias/fornecedores/catálogo) */
    .cat-grid, .sup-grid{ grid-template-columns: 1fr !important; }

    /* ---------- Tabela em CARTÕES no mobile (data-mob="cards") ---------- */
    table[data-mob="cards"]{ min-width:0 !important; }
    table[data-mob="cards"] thead{ display:none; }
    table[data-mob="cards"], table[data-mob="cards"] tbody{ display:block; width:100%; }
    table[data-mob="cards"] tr{ display:block; background:var(--panel-2,#0b1220); border:1px solid var(--border,rgba(255,255,255,.10)); border-radius:12px; margin:0 0 12px; padding:6px 14px; }
    table[data-mob="cards"] td{ display:flex; justify-content:space-between; align-items:center; gap:14px; border:0 !important; padding:9px 0 !important; text-align:right !important; }
    table[data-mob="cards"] td + td{ border-top:1px solid var(--border,rgba(255,255,255,.06)) !important; }
    table[data-mob="cards"] td[data-label]::before{ content:attr(data-label); color:var(--muted,#94a3b8); font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; text-align:left; flex:0 0 auto; }
    table[data-mob="cards"] td:first-child:not([data-label]){ display:none; }      /* checkbox */
    table[data-mob="cards"] td:first-child[data-label]{ border-top:0 !important; }
    table[data-mob="cards"] td .rp-qty, table[data-mob="cards"] .col-acoes, table[data-mob="cards"] .acts-cell, table[data-mob="cards"] .mov-actions-cell{ justify-content:flex-end; }
    table[data-mob="cards"] .rp-bar{ width:80px; }
    table[data-mob="cards"] td[data-label]{ flex-wrap:wrap; }
  }

  @media (max-width: 560px){
    .prod-kpis, .dash-kpis, .rp-kpis, .cat-stats, .sup-stats{ grid-template-columns: 1fr !important; }
    .main{ padding-left:10px !important; padding-right:10px !important; }
  }
</style>

<script>
(function(){
  /* ---- Menu lateral mobile ---- */
  function initMenu(){
    var sidebar = document.querySelector('.sidebar');
    if (!sidebar || document.querySelector('.sp-hamb')) return;
    var hamb = document.createElement('button');
    hamb.className = 'sp-hamb'; hamb.type = 'button'; hamb.setAttribute('aria-label','Menu'); hamb.innerHTML = '&#9776;';
    var ovl = document.createElement('div'); ovl.className = 'sp-ovl';
    document.body.appendChild(hamb); document.body.appendChild(ovl);
    function open(){ sidebar.classList.add('sp-open'); ovl.classList.add('sp-open'); }
    function close(){ sidebar.classList.remove('sp-open'); ovl.classList.remove('sp-open'); }
    hamb.addEventListener('click', function(){ sidebar.classList.contains('sp-open') ? close() : open(); });
    ovl.addEventListener('click', close);
    sidebar.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', close); });
    window.addEventListener('resize', function(){ if (window.innerWidth > 992) close(); });
  }

  /* ---- Barra de carregamento ---- */
  function initLoadbar(){
    var bar = document.getElementById('spLoadbar');
    if (!bar) return;
    var timer = null, finishTimer = null;

    function start(){
      clearTimeout(finishTimer);
      bar.classList.add('sp-active');
      bar.style.width = '0';
      void bar.offsetWidth;            // força reflow
      bar.style.width = '80%';
      clearTimeout(timer);
      timer = setTimeout(function(){ bar.style.width = '90%'; }, 400);
    }
    function finish(){
      clearTimeout(timer);
      bar.style.width = '100%';
      finishTimer = setTimeout(function(){ bar.classList.remove('sp-active'); bar.style.width = '0'; }, 280);
    }
    // Pulso: para ações que NÃO recarregam a página (abrir modal, drawer, etc.)
    function pulse(){ start(); clearTimeout(finishTimer); finishTimer = setTimeout(finish, 550); }

    // Clique em qualquer elemento clicável
    document.addEventListener('click', function(e){
      var el = e.target.closest ? e.target.closest('a, button, [role="button"], .btn') : null;
      if (!el || el.disabled) return;

      // Links
      if (el.tagName === 'A'){
        var href = el.getAttribute('href') || '';
        if (el.target === '_blank' || href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0){
          pulse(); return;                                  // não navega -> pulso
        }
        if (el.hostname && el.hostname !== window.location.hostname) return; // link externo
        start(); return;                                    // navega -> completa no pageshow
      }

      // Botões
      var type = (el.getAttribute('type') || '').toLowerCase();
      var inForm = el.closest('form');
      if (type === 'submit' || (inForm && type !== 'button')){
        start(); return;                                    // vai enviar o form -> completa na navegação
      }
      pulse();                                              // ação via JS -> pulso e finaliza sozinho
    }, true);

    // Envio de formulário (inclusive programático)
    document.addEventListener('submit', function(){ start(); }, true);

    // Completa ao entrar/voltar para a página
    window.addEventListener('pageshow', function(){ finish(); });
  }

  function init(){ initMenu(); initLoadbar(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
<style>
  /* Utilities mínimos p/ o paginador do Laravel (o app não carrega o CSS do Bootstrap) */
  .d-none{ display:none !important; }
  .d-flex{ display:flex !important; }
  .flex-fill{ flex:1 1 auto !important; }
  .justify-content-between{ justify-content:space-between !important; }
  @media (min-width: 576px){
    .d-sm-flex{ display:flex !important; }
    .d-sm-none{ display:none !important; }
  }
</style>

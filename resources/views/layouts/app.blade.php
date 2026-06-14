<!doctype html>
<html lang="pt-br" data-theme="{{ optional(auth()->user())->theme ?? 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Nexo Estoque')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-pro.css') }}?v={{ @filemtime(public_path('css/dashboard-pro.css')) }}">

    @if(request()->routeIs('movements.*'))
        <link rel="stylesheet" href="{{ asset('css/movimentacoes-pro.css') }}?v={{ @filemtime(public_path('css/movimentacoes-pro.css')) }}">
    @endif

    <style>
      .btn-sm { padding: .5rem .75rem; }
      .brand-small { font-size: 1rem; }
      .table-min { min-width:720px; }

      @media (max-width:720px){ .desktop-only{display:none!important;} .mobile-only{display:block!important;} }
      @media (min-width:721px){ .desktop-only{display:block!important;} .mobile-only{display:none!important;} }

      /* ===== Tom Select — segue o tema (claro/escuro) ===== */
      .ts-wrapper{ font-size:.95rem; }
      .ts-control{
        background: var(--t-input-bg) !important;
        border:1px solid var(--t-input-border) !important;
        color: var(--t-text) !important;
        border-radius:.375rem; min-height:38px; box-shadow:none !important;
      }
      .ts-control input, .ts-control .item{ color: var(--t-text) !important; }
      .ts-control .item{ background: rgba(124,92,252,.22); border-radius:6px; padding:1px 8px; }
      .ts-wrapper.focus .ts-control{ border-color: var(--accent, #3b82f6) !important; }
      .ts-dropdown{
        background: var(--t-panel); color: var(--t-text);
        border:1px solid var(--t-border); border-radius:.5rem; overflow:hidden;
        z-index: 4050; /* acima do modal do Bootstrap */
      }
      .ts-dropdown .option{ color: var(--t-text); padding:8px 12px; }
      .ts-dropdown .option.active{ background: rgba(124,92,252,.35); color:#fff; }
      .ts-dropdown .create{ color: var(--t-muted); padding:8px 12px; }
      .ts-dropdown .create strong{ color: var(--t-text); }
      .ts-control input::placeholder{ color: var(--t-muted) !important; }
    </style>
</head>

<body>
  @auth
    @include('layouts.partials.sidebar')
  @endauth

  @include('layouts.partials.toast')

  <main class="{{ auth()->check() ? 'main' : '' }}
            {{ auth()->check() ? 'main-dark' : '' }}">
    <div id="topNotice" class="top-notice" role="status" aria-live="polite">
        <span id="topNoticeMsg"></span>
      </div>
      @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
    (function(){
      function build(el, extra){
        if (el.tomselect) return;
        var opts = Object.assign({ allowEmptyOption:true, maxOptions:null }, extra);
        // Se o select está dentro de um modal, joga a lista no body para não ser cortada.
        if (el.closest('.modal')) { opts.dropdownParent = 'body'; }
        new TomSelect(el, opts);
      }

      function initSelects(){
        if (typeof TomSelect === 'undefined') return;

        document.querySelectorAll('select.js-search-create').forEach(function(el){
          build(el, {
            create: true,
            createOnBlur: true,
            persist: false,
            render: {
              option_create: function(data, escape){
                return '<div class="create">+ Criar <strong>' + escape(data.input) + '</strong></div>';
              },
              no_results: function(){ return '<div class="create">Nada encontrado</div>'; }
            }
          });
        });

        document.querySelectorAll('select.js-search').forEach(function(el){
          build(el, { create:false });
        });
      }

      if (document.readyState !== 'loading') initSelects();
      else document.addEventListener('DOMContentLoaded', initSelects);
    })();
  </script>
</body>
</html>

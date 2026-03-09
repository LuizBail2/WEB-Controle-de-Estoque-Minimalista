<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Controle de Estoque')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-pro.css') }}?v={{ time() }}">

    @if(request()->routeIs('movements.*'))
        <link rel="stylesheet" href="{{ asset('css/movimentacoes-pro.css') }}?v={{ time() }}">
    @endif

    <style>
      .btn-sm { padding: .5rem .75rem; }
      .brand-small { font-size: 1rem; }
      .table-min { min-width:720px; }

      @media (max-width:720px){
        .desktop-only { display:none!important; }
        .mobile-only { display:block!important; }
      }
      @media (min-width:721px){
        .desktop-only { display:block!important; }
        .mobile-only { display:none!important; }
      }
    </style>
</head>

<body>
  @auth
    @include('layouts.partials.sidebar')
  @endauth

  <main class="{{ auth()->check() ? 'main' : '' }}
            {{ (request()->routeIs('products.*') || request()->routeIs('movements.*') || request()->routeIs('dashboard')) ? 'main-dark' : '' }}">
    <div id="topNotice" class="top-notice" role="status" aria-live="polite">
        <span id="topNoticeMsg"></span>
      </div>
      @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Controle de Estoque')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      /* ajustes leves para touch e responsividade */
      .btn-sm { padding: .5rem .75rem; }
      .brand-small { font-size: 1rem; }
      /* tabela min-width para permitir scroll horizontal em telas pequenas */
      .table-min { min-width:720px; }
      /* cards mobile (opcional se quiser usar) */
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

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container d-flex justify-content-between align-items-center">

        <a class="navbar-brand mb-0" href="{{ route('dashboard') }}">
            Controle de Estoque Eletrônico
        </a>

        <div class="d-flex align-items-center gap-2">

            @auth
                <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-sm">
                    Produtos
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-danger btn-sm">Sair</button>
                </form>
            @endauth

            @guest
                <a href="{{ route('login') }}"></a>
            @endguest

        </div>

    </div>
</nav>


<div class="container">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</div>
  <!-- Bootstrap JS bundle --> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

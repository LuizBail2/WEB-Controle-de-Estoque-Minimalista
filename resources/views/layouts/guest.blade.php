<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Login')</title>

  {{-- Fonte e CSS do tema --}}
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/login-pro.css') }}">
</head>
<body>
  @yield('content')
</body>
</html>
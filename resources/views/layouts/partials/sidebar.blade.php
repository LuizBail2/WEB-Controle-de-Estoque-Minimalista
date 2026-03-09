<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:20px;height:20px;object-fit:contain;">
    </div>
    <div class="logo-text">Stock <span>Manager</span></div>
  </div>

  <div class="sidebar-section">Principal</div>

  <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
    <span class="icon">🏠</span> Dashboard
  </a>

  <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
    <span class="icon">📦</span> Produtos
  </a>

  <a class="nav-item {{ request()->routeIs('movements.*') ? 'active' : '' }}" href="{{ route('movements.index') }}">
    <span class="icon">↔️</span> Movimentações
  </a>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</div>
      <div style="flex:1">
        <div class="user-name">{{ auth()->user()->name ?? 'Administrador' }}</div>
        <div class="user-role">Administrador</div>
      </div>
      <span style="color:var(--muted);font-size:16px">⚙️</span>
    </div>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px;">
      @csrf
      <button type="submit" class="btn-logout">Sair</button>
    </form>
  </div>
</aside>
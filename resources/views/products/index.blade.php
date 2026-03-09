@extends('layouts.app')

@section('title', 'Gerenciador de Eletrônicos')

@section('content')
<div class="products-dark">

<form method="GET" action="{{ route('products.index') }}" class="row g-2 mb-3">

    <div class="col-12 col-md-4">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Pesquisar produto..."
            value="{{ request('search') }}"
        >
    </div>

    <div class="col-12 col-md-3">
        <select name="category" class="form-select">
            <option value="">Todas as categorias</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                    {{ $category }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-3">
        <select name="status" class="form-select">
            <option value="">Todos os status</option>
            <option value="ok" {{ request('status') == 'ok' ? 'selected' : '' }}>Estoque OK</option>
            <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Estoque baixo</option>
            <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Sem estoque</option>
        </select>
    </div>

    <div class="col-12 col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100">🔎 Buscar</button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">Limpar</a>
    </div>
</form>

@if($products->count() === 0)
    <div class="alert alert-warning">
        Nenhum produto encontrado com os filtros informados.
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-5">
    <h3>Produtos</h3>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.lowstock', request()->only('category')) }}"
           class="btn btn-outline-danger"
           target="_blank">
            PDF: Estoque Baixo
        </a>

        <a href="{{ route('products.create') }}" class="btn btn-success">Novo Produto</a>
    </div>
</div>

<div class="table-responsive desktop-only shadow-sm mb-3">
    <table class="table table-striped table-hover table-min">
        <thead class="table-dark">
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Quantidade</th>
                <th>Qtd Mínimo</th>
                <th>Preço</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->category ?? '-' }}</td>
                <td>{{ $p->quantity }}</td>
                <td>{{ $p->minimum_quantity }}</td>
                <td>
                    @if($p->price)
                        R$ {{ number_format($p->price, 2, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($p->quantity == 0)
                        <span class="badge bg-dark">Sem estoque</span>
                    @elseif($p->quantity <= $p->minimum_quantity)
                        <span class="badge bg-warning text-dark">Baixo</span>
                    @else
                        <span class="badge bg-success">OK</span>
                    @endif
                </td>

                <td class="actions-cell">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-eye js-product-view"
                            data-url="{{ route('products.show', $p) }}"
                            title="Ver">👁</button>

                    <button type="button"
                            class="btn btn-sm btn-primary js-product-edit"
                            data-url="{{ route('products.edit', $p) }}?modal=1">Editar</button>

                    <form action="{{ route('products.destroy', $p) }}" class="d-inline" method="POST" onsubmit="return confirm('Excluir produto?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Excluir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Nenhum produto encontrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mobile-only">
    @forelse($products as $p)
        <div class="card mb-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title mb-1">{{ $p->name }}</h5>
                        <p class="mb-1 small text-muted">Categoria: {{ $p->category ?? '-' }}</p>
                        <p class="mb-1 small">Qtd: <strong>{{ $p->quantity }}</strong> • Qtd Mín: <strong>{{ $p->minimum_quantity }}</strong></p>
                        <p class="mb-0 small">Preço:
                            @if($p->price)
                                R$ {{ number_format($p->price, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </p>
                    </div>

                    <div style="min-width:86px; text-align:right;">
                        @if($p->quantity == 0)
                            <span class="badge bg-dark">Sem estoque</span>
                        @elseif($p->quantity <= $p->minimum_quantity)
                            <span class="badge bg-warning text-dark">Baixo</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-eye js-product-view flex-fill"
                        data-url="{{ route('products.show', $p) }}"
                        title="Ver">👁 Ver</button>

                    <button type="button"
                        class="btn btn-sm btn-primary js-product-edit flex-fill"
                        data-url="{{ route('products.edit', $p) }}?modal=1">Editar</button>

                    <form action="{{ route('products.destroy', $p) }}" method="POST" class="d-inline flex-fill" onsubmit="return confirm('Excluir produto?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger w-100">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="text-muted">Nenhum produto encontrado.</div>
    @endforelse
</div>

<div class="d-flex justify-content-between align-items-center my-4">
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">← Voltar para o Home</a>
    <div>{{ $products->links() }}</div>
</div>

</div> {{-- /products-dark --}}

{{-- DRAWER --}}
<div class="product-drawer" id="productDrawer" aria-hidden="true">
  <div class="drawer-backdrop" id="drawerBackdrop"></div>

  <aside class="drawer-panel" role="dialog" aria-modal="true" aria-label="Detalhes do Produto">
    <div class="drawer-header">
      <h5 class="drawer-title">Detalhes do Produto</h5>
      <button class="drawer-close" id="drawerClose" type="button">✕</button>
    </div>

    <div class="drawer-body" id="drawerBody">
      <div class="drawer-loading">Carregando...</div>
    </div>

    <div class="drawer-footer">
      <a href="#" class="drawer-edit" id="drawerEdit">✏️ Editar produto</a>
    </div>
  </aside>
</div>

{{-- MODAL --}}
<div class="center-modal" id="centerModal" aria-hidden="true">
  <div class="center-modal__backdrop" data-modal-close></div>

  <div class="center-modal__panel" role="dialog" aria-modal="true">
    <div class="center-modal__header">
      <div class="center-modal__title">✏️ Editar Produto</div>
      <button class="center-modal__close" type="button" data-modal-close>✕</button>
    </div>

    <div class="center-modal__body" id="centerModalBody">
      <div class="center-modal__loading">Carregando...</div>
    </div>
  </div>
</div>

{{-- TOAST --}}
<div id="toast" class="toast" aria-live="polite" aria-atomic="true">
  <span id="toastMsg">Mensagem</span>
</div>

<script>
(function(){

  const drawer = document.getElementById('productDrawer');
  const drawerBody = document.getElementById('drawerBody');
  const drawerClose = document.getElementById('drawerClose');
  const drawerBackdrop = document.getElementById('drawerBackdrop');
  const drawerEdit = document.getElementById('drawerEdit');

  function openDrawer(){
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer(){
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  async function loadProduct(url){
    drawerBody.innerHTML = `<div class="drawer-loading">Carregando...</div>`;
    openDrawer();

    try{
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
      if(!res.ok) throw new Error('Falha ao carregar');
      const p = await res.json();

      drawerEdit.href = "#";
      drawerEdit.dataset.url = (p.edit_url || '') + "?modal=1";
      drawerEdit.classList.add("js-product-edit");

      const price = p.price ? `R$ ${Number(p.price).toFixed(2).replace('.', ',')}` : '-';
      const stockValue = (p.stock_value != null) ? `R$ ${Number(p.stock_value).toFixed(2).replace('.', ',')}` : '-';

      drawerBody.innerHTML = `
        <div class="drawer-card">
          <div class="drawer-product-name">${p.name}</div>
          <div class="drawer-meta">${p.category ?? '-'}</div>
          <span class="drawer-badge">${p.status}</span>
        </div>

        <div class="drawer-grid">
          <div class="drawer-row"><span>Quantidade em estoque</span><strong>${p.quantity}</strong></div>
          <div class="drawer-row"><span>Quantidade mínima</span><strong>${p.minimum_quantity}</strong></div>
          <div class="drawer-row"><span>Preço unitário</span><strong>${price}</strong></div>
          <div class="drawer-row"><span>Valor em estoque</span><strong class="drawer-green">${stockValue}</strong></div>
        </div>
      `;
    }catch(e){
      drawerBody.innerHTML = `<div class="drawer-error">Não foi possível carregar os detalhes.</div>`;
    }
  }


  const modal = document.getElementById('centerModal');
  const modalBody = document.getElementById('centerModalBody');

  function openModal(){
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
  }
  function closeModal(){
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
  }

  function showToast(message){
    const toast = document.getElementById('toast');
    const msg = document.getElementById('toastMsg');
    msg.textContent = message;

    toast.classList.add('show');
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => {
      toast.classList.remove('show');
    }, 2500);
  }

  async function loadEditForm(url){
    openModal();

    let timer = setTimeout(() => {
      modalBody.innerHTML = `<div class="center-modal__spinner"></div>`;
    }, 250);

    try{
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
      });
      const html = await res.text();

      clearTimeout(timer);

      if(!res.ok){
        modalBody.innerHTML = `<div class="center-modal__loading">Erro ${res.status}</div>`;
        return;
      }

      modalBody.innerHTML = html;
    }catch(e){
      clearTimeout(timer);
      modalBody.innerHTML = `<div class="center-modal__loading">Falha ao carregar</div>`;
    }
  }


  document.addEventListener('click', (ev) => {
    const viewBtn = ev.target.closest('.js-product-view');
    if(viewBtn){
      ev.preventDefault();
      loadProduct(viewBtn.dataset.url);
      return;
    }

    const editBtn = ev.target.closest('.js-product-edit');
    if(editBtn){
      ev.preventDefault();
      loadEditForm(editBtn.dataset.url);
      return;
    }

    if(ev.target.closest('[data-modal-close]')){
      closeModal();
    }
  });

  drawerClose.addEventListener('click', closeDrawer);
  drawerBackdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape'){
      closeDrawer();
      closeModal();
    }
  });

  
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('#editProductForm');
    if(!form) return;

    e.preventDefault();

    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: new FormData(form)
    });

    if(res.ok){
      closeModal();
      closeDrawer();
      showToast("Produto editado com sucesso ✅");
      setTimeout(() => window.location.reload(), 800);
    }else{
      let data=null;
      try{ data = await res.json(); }catch(_){}
      const msg = data?.message || Object.values(data?.errors || {})[0]?.[0] || 'Erro ao salvar';
      alert(msg);
    }
  });
})();
</script>

@endsection
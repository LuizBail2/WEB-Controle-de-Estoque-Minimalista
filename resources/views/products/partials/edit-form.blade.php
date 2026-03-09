<form id="editProductForm" method="POST" action="{{ route('products.update', $product) }}">
  @csrf
  @method('PUT')

  <div class="mb-3">
    <label class="form-label">Nome</label>
    <input name="name" class="form-control" value="{{ $product->name }}" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Categoria</label>
    <input name="category" class="form-control" value="{{ $product->category }}">
  </div>

  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Quantidade</label>
      <input type="number" name="quantity" class="form-control" value="{{ $product->quantity }}" min="0" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Qtd mínima</label>
      <input type="number" name="minimum_quantity" class="form-control" value="{{ $product->minimum_quantity }}" min="0" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Preço (R$)</label>
      <input type="number" step="0.01" name="price" class="form-control" value="{{ $product->price }}">
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-4">
    <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar alterações</button>
  </div>
</form>
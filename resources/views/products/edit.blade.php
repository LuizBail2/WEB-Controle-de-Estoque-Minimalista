@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')
<div class="product-form-page" style="max-width:680px;">
    <h3 class="mb-3">Novo Produto</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Confira os campos abaixo:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('products.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nome do produto *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="row g-3">
            <div class="col-md-6 mb-1">
                <label class="form-label">SKU / Código</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Ex: BRK-001">
            </div>
            <div class="col-md-6 mb-1">
                <label class="form-label">Categoria</label>
                <select name="category" class="form-select js-search-create" autocomplete="off">
                    <option value="">Selecionar...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                    @if(old('category') && !$categories->contains(old('category')))
                        <option value="{{ old('category') }}" selected>{{ old('category') }}</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-md-4 mb-1">
                <label class="form-label">Quantidade *</label>
                <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 0) }}" min="0" required>
            </div>
            <div class="col-md-4 mb-1">
                <label class="form-label">Quantidade mínima *</label>
                <input type="number" name="minimum_quantity" class="form-control" value="{{ old('minimum_quantity', 5) }}" min="0" required>
            </div>
            <div class="col-md-4 mb-1">
                <label class="form-label">Preço (R$)</label>
                <input type="number" name="price" class="form-control" value="{{ old('price') }}" step="0.01" min="0" placeholder="0,00">
            </div>
        </div>

        <hr style="border-color: var(--border, rgba(255,255,255,.12)); margin:18px 0 12px;">
        <div style="text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; color: var(--muted, #94a3b8); margin-bottom:10px;">Informações adicionais</div>

        <div class="row g-3">
            <div class="col-md-6 mb-1">
                <label class="form-label">Fornecedor</label>
                <select name="supplier" class="form-select js-search-create" autocomplete="off">
                    <option value="">Nenhum</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup }}" {{ old('supplier') == $sup ? 'selected' : '' }}>{{ $sup }}</option>
                    @endforeach
                    @if(old('supplier') && !$suppliers->contains(old('supplier')))
                        <option value="{{ old('supplier') }}" selected>{{ old('supplier') }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-6 mb-1">
                <label class="form-label">Localização</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Ex: Prateleira A3">
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label">Observação</label>
            <textarea name="note" class="form-control" rows="2" placeholder="Informações adicionais...">{{ old('note') }}</textarea>
        </div>

        <hr>
        <h6 class="mb-2">Lote / Validade (opcional)</h6>
        <div class="row g-3 mb-2">
            <div class="col-md-4"><label class="form-label">Lote</label><input type="text" name="batch_lote" class="form-control" value="{{ old('batch_lote') }}" placeholder="Ex: L2026-04"></div>
            <div class="col-md-4"><label class="form-label">Validade</label><input type="date" name="batch_expiry" class="form-control" value="{{ old('batch_expiry') }}"></div>
            <div class="col-md-4"><label class="form-label">Qtd. do lote</label><input type="number" name="batch_quantity" class="form-control" min="1" value="{{ old('batch_quantity') }}" placeholder="0"></div>
        </div>
        <small class="text-secondary d-block mb-3">Preencha validade e quantidade para registrar um lote — aparece em Validades e nos detalhes do produto.</small>

        <button class="btn btn-primary">Salvar</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection

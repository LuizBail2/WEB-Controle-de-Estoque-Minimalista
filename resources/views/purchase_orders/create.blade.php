@extends('layouts.app')

@section('title', 'Novo Pedido de Compra')

@section('content')
<div class="reports-dark po">

    <div class="po-header">
        <div>
            <h3 class="po-title">Novo Pedido de Compra</h3>
            <p class="po-sub">Monte o pedido. O estoque só será alterado quando você receber.</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-light btn-sm">← Voltar</a>
    </div>

    @if($errors->any())
        <div class="po-alert">{{ $errors->first() }}</div>
    @endif

    @if($products->isEmpty() || $suppliers->isEmpty())
        <div class="po-warn">
            ⚠️ Você precisa ter pelo menos <strong>1 fornecedor</strong> e <strong>1 produto</strong> cadastrados para criar um pedido.
        </div>
    @endif

    <form method="POST" action="{{ route('purchase-orders.store') }}" id="poForm">
        @csrf
        <div class="po-panel">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="po-label">Fornecedor *</label>
                    <select name="supplier_id" class="rp-sel w-100" required>
                        <option value="">Selecione o fornecedor...</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="po-label">Observação</label>
                    <input type="text" name="note" class="rp-input w-100" value="{{ old('note') }}" placeholder="Opcional">
                </div>
            </div>
        </div>

        <div class="po-panel">
            <div class="po-panel__title">Itens do pedido</div>

            <div class="table-responsive">
                <table class="table rp-table align-middle mb-0" id="poItems">
                    <thead>
                        <tr>
                            <th style="width:45%;">Produto</th>
                            <th style="width:15%;">Quantidade</th>
                            <th style="width:20%;">Preço unit. (R$)</th>
                            <th style="width:15%;" class="text-end">Subtotal</th>
                            <th style="width:5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="poItemsBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end" style="border:0;"><strong>Total do pedido</strong></td>
                            <td class="text-end" style="border:0;"><strong class="po-total" id="poTotal">R$ 0,00</strong></td>
                            <td style="border:0;"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-light btn-sm mt-3" id="poAddItem">+ Adicionar item</button>
        </div>

        <div class="po-actions">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Pedido</button>
        </div>
    </form>
</div>

{{-- template de linha --}}
<template id="poRowTpl">
    <tr class="po-row">
        <td>
            <select name="items[__I__][product_id]" class="rp-sel w-100 po-prod" required>
                <option value="">Selecione...</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-price="{{ $p->price ?? 0 }}">{{ $p->name }} {{ $p->code ? '(' . $p->code . ')' : '' }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__I__][quantity]" class="rp-input w-100 po-qty" min="1" value="1" required></td>
        <td><input type="number" name="items[__I__][unit_price]" class="rp-input w-100 po-price" min="0" step="0.01" placeholder="0,00"></td>
        <td class="text-end po-subtotal">R$ 0,00</td>
        <td class="text-center"><button type="button" class="po-remove" title="Remover">🗑️</button></td>
    </tr>
</template>

<style>
    .po{ color: var(--t-text); }
    .po-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .po-title{ margin:0; font-weight:800; } .po-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .po-alert{ background: rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.4); color:#ef4444; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:.9rem; }
    .po-warn{ background: rgba(245,200,66,.10); border:1px solid rgba(245,200,66,.35); color:#ffb700; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:.9rem; }

    .po-panel{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:18px; margin-bottom:16px; }
    .po-panel__title{ font-weight:700; margin-bottom:12px; }
    .po-label{ font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; color: var(--t-muted); display:block; margin-bottom:6px; }
    .rp-input, .rp-sel{ height:42px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none;  }
    .rp-input::placeholder{ color: var(--t-muted); }
    .w-100{ width:100%; }

    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: transparent !important; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .po-subtotal{ font-weight:600; color:#22c55e; }
    .po-total{ color:#22c55e; font-size:1.1rem; }
    .po-remove{ background: rgba(239,68,68,.12); color:#ef4444; border:1px solid rgba(239,68,68,.3); border-radius:8px; width:32px; height:32px; cursor:pointer; }
    .po-remove:hover{ background: rgba(239,68,68,.25); }

    .po-actions{ display:flex; gap:10px; justify-content:flex-end; }
</style>

<script>
  (function(){
    var body = document.getElementById('poItemsBody');
    var tpl  = document.getElementById('poRowTpl').innerHTML;
    var addBtn = document.getElementById('poAddItem');
    var totalEl = document.getElementById('poTotal');
    var form = document.getElementById('poForm');
    var counter = 0;

    function brl(n){ return 'R$ ' + (n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function recalc(){
      var total = 0;
      body.querySelectorAll('.po-row').forEach(function(row){
        var qty = parseFloat(row.querySelector('.po-qty').value) || 0;
        var price = parseFloat(row.querySelector('.po-price').value) || 0;
        var sub = qty * price;
        row.querySelector('.po-subtotal').textContent = brl(sub);
        total += sub;
      });
      totalEl.textContent = brl(total);
    }

    function addRow(){
      var html = tpl.replace(/__I__/g, counter++);
      var tr = document.createElement('tbody');
      tr.innerHTML = html;
      var row = tr.firstElementChild;
      body.appendChild(row);

      // ao escolher produto, sugere o preço atual dele
      var prod = row.querySelector('.po-prod');
      var price = row.querySelector('.po-price');
      prod.addEventListener('change', function(){
        var opt = prod.selectedOptions[0];
        if (opt && opt.dataset.price && !price.value) price.value = parseFloat(opt.dataset.price).toFixed(2);
        recalc();
      });
      row.querySelector('.po-qty').addEventListener('input', recalc);
      price.addEventListener('input', recalc);
      row.querySelector('.po-remove').addEventListener('click', function(){
        row.remove();
        if (!body.querySelector('.po-row')) addRow();
        recalc();
      });
      recalc();
    }

    addBtn.addEventListener('click', addRow);
    addRow(); // começa com 1 linha

    form.addEventListener('submit', function(e){
      if (!body.querySelector('.po-row')){ e.preventDefault(); alert('Adicione pelo menos um produto.'); }
    });
  })();
</script>
@endsection

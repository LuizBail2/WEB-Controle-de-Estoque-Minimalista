@extends('layouts.app')

@section('title', 'Pedido ' . $purchaseOrder->code)

@section('content')
<div class="reports-dark po">

    <div class="po-header">
        <div>
            <h3 class="po-title">Pedido {{ $purchaseOrder->code }}</h3>
            <p class="po-sub">
                Fornecedor: <strong>{{ $purchaseOrder->supplier->name ?? '—' }}</strong>
                &nbsp;•&nbsp; Criado em {{ $purchaseOrder->created_at->format('d/m/Y') }}
                @if($purchaseOrder->received_at) &nbsp;•&nbsp; Recebido em {{ $purchaseOrder->received_at->format('d/m/Y') }} @endif
            </p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-light btn-sm">← Voltar</a>
    </div>

    {{-- Faixa de status --}}
    <div class="po-statusbar">
        @if($purchaseOrder->status === 'recebido')
            <span class="po-badge po-badge--green">✓ Recebido — entradas lançadas no estoque</span>
        @elseif($purchaseOrder->status === 'cancelado')
            <span class="po-badge po-badge--red">✕ Cancelado</span>
        @else
            <span class="po-badge po-badge--yellow">⏳ Pendente — ainda não deu entrada no estoque</span>
        @endif

        @if($purchaseOrder->isPendingApproval())
            <span class="po-badge po-badge--yellow">🕓 Aguardando aprovação do administrador</span>
        @elseif($purchaseOrder->isRejected())
            <span class="po-badge po-badge--red">✕ Rejeitado pelo administrador</span>
        @elseif($purchaseOrder->approved_at)
            <span class="po-badge po-badge--green">✓ Aprovado</span>
        @endif

        <a href="{{ route('purchase-orders.report', $purchaseOrder) }}" target="_blank" class="po-badge po-badge--blue" style="text-decoration:none;">📄 Baixar PDF da solicitação</a>
    </div>

    @if($purchaseOrder->note)
        <div class="po-note">📝 {{ $purchaseOrder->note }}</div>
    @endif

    <div class="rp-report">
        <div class="table-responsive">
            <table class="table rp-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Quantidade</th>
                        <th class="text-end">Preço unit.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->product->name ?? '(produto removido)' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">R$ {{ number_format($item->unit_price ?? 0, 2, ',', '.') }}</td>
                            <td class="text-end"><span class="rp-money">R$ {{ number_format($item->quantity * ($item->unit_price ?? 0), 2, ',', '.') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end" style="border:0;"><strong>Total</strong></td>
                        <td class="text-end" style="border:0;"><strong class="po-total">R$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Ações --}}
    <div class="po-actions">
        @if($purchaseOrder->status === 'pendente')
            <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" id="formCancel" style="display:none;">@csrf</form>
            <form method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder) }}" id="formReceive" style="display:none;">@csrf</form>
            <button type="button" class="btn btn-outline-secondary po-confirm"
                    data-form="formCancel" data-variant="warn"
                    data-title="Cancelar pedido?"
                    data-text="O pedido será marcado como cancelado. O estoque não é alterado.">Cancelar pedido</button>

            @if($purchaseOrder->isApproved())
                <button type="button" class="btn btn-success po-confirm"
                        data-form="formReceive" data-variant="ok"
                        data-title="Confirmar recebimento?"
                        data-text="Isso vai somar tudo no estoque e atualizar os preços dos produtos.">📥 Receber pedido (dar entrada no estoque)</button>
            @else
                <button type="button" class="btn btn-success" disabled
                        title="O pedido precisa ser aprovado pelo administrador antes de dar entrada."
                        style="opacity:.5;cursor:not-allowed;">🔒 Receber pedido (aguardando aprovação)</button>
            @endif

            {{-- Admin: aprovar / rejeitar quando aguardando --}}
            @if(auth()->user()->isAdmin() && $purchaseOrder->isPendingApproval())
                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}" id="formApprove" style="display:none;">@csrf</form>
                <form method="POST" action="{{ route('purchase-orders.reject', $purchaseOrder) }}" id="formReject" style="display:none;">@csrf</form>
                <button type="button" class="btn btn-success po-confirm"
                        data-form="formApprove" data-variant="ok"
                        data-title="Aprovar pedido?"
                        data-text="O funcionário poderá dar entrada no estoque depois disso.">✓ Aprovar</button>
                <button type="button" class="btn btn-outline-danger po-confirm"
                        data-form="formReject" data-variant="danger"
                        data-title="Rejeitar pedido?"
                        data-text="O pedido será marcado como rejeitado e não poderá dar entrada.">✕ Rejeitar</button>
            @endif
        @else
            <form method="POST" action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" id="formDestroy" style="display:none;">@csrf @method('DELETE')</form>
            <button type="button" class="btn btn-outline-danger po-confirm"
                    data-form="formDestroy" data-variant="danger"
                    data-title="Excluir do histórico?"
                    data-text="Remove este pedido do histórico. Não mexe no estoque.">Excluir do histórico</button>
        @endif
    </div>

    {{-- Modal de confirmação --}}
    <div class="poc-overlay" id="pocOverlay">
      <div class="poc-box">
        <div class="poc-ico" id="pocIco">⚠️</div>
        <h5 class="poc-title" id="pocTitle">Confirmar?</h5>
        <p class="poc-text" id="pocText"></p>
        <div class="poc-actions">
          <button type="button" class="btn btn-secondary" id="pocCancel">Voltar</button>
          <button type="button" class="btn poc-confirm" id="pocConfirm">Confirmar</button>
        </div>
      </div>
    </div>
</div>

<style>
    .po{ color: var(--t-text); }
    .po-header{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
    .po-title{ margin:0; font-weight:800; } .po-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.88rem; }
    .po-statusbar{ margin-bottom:14px; }
    .po-note{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:12px; padding:10px 14px; margin-bottom:14px; color: var(--t-muted); font-size:.9rem; }
    .po-badge{ display:inline-block; padding:5px 14px; border-radius:999px; font-size:.82rem; font-weight:600; }
    .po-badge--green{ background:rgba(34,197,94,.15); color:#22c55e; }
    .po-badge--yellow{ background:rgba(245,200,66,.15); color:#ffb700; }
    .po-badge--blue{ background:rgba(59,130,246,.15); color:#60a5fa; }
    .po-statusbar{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .po-badge--red{ background:rgba(239,68,68,.15); color:#ef4444; }

    .rp-report{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; overflow:hidden; margin-bottom:16px; }
    .rp-table, .rp-table > :not(caption) > * > *{ background-color: transparent !important; color: var(--t-text) !important; box-shadow:none !important; }
    .rp-table thead th{ color: var(--t-muted) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--t-border) !important; background: var(--t-panel-2) !important; }
    .rp-table tbody td{ border-bottom:1px solid var(--t-border-soft) !important; vertical-align:middle; }
    .rp-money{ color:#22c55e; font-weight:600; } .po-total{ color:#22c55e; font-size:1.1rem; }

    .po-actions{ display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; }
    .po-actions form{ margin:0; }
</style>

<style>
  .poc-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.55); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:2000; }
  .poc-overlay.open{ display:flex; }
  .poc-box{ background:var(--t-panel-2); border:1px solid var(--t-border); border-radius:18px; padding:30px 26px; max-width:440px; width:92%; text-align:center; color:var(--t-text); box-shadow:0 30px 60px rgba(0,0,0,.5); }
  .poc-ico{ font-size:40px; margin-bottom:10px; }
  .poc-title{ font-weight:800; margin:0 0 8px; }
  .poc-text{ color:var(--t-muted); font-size:.9rem; margin:0 0 22px; line-height:1.5; }
  .poc-actions{ display:flex; gap:10px; justify-content:center; }
  .poc-confirm{ font-weight:600; }
  .poc-confirm.v-ok{ background:rgba(34,197,94,.16); color:#22c55e; border:1px solid rgba(34,197,94,.5); }
  .poc-confirm.v-ok:hover{ background:rgba(34,197,94,.3); color:#22c55e; }
  .poc-confirm.v-warn{ background:rgba(245,200,66,.16); color:#ffb700; border:1px solid rgba(245,200,66,.5); }
  .poc-confirm.v-warn:hover{ background:rgba(245,200,66,.3); color:#ffb700; }
  .poc-confirm.v-danger{ background:rgba(239,68,68,.16); color:#ef4444; border:1px solid rgba(239,68,68,.5); }
  .poc-confirm.v-danger:hover{ background:rgba(239,68,68,.3); color:#ef4444; }
</style>

<script>
(function(){
  var overlay=document.getElementById('pocOverlay');
  var titleEl=document.getElementById('pocTitle');
  var textEl=document.getElementById('pocText');
  var icoEl=document.getElementById('pocIco');
  var confirmBtn=document.getElementById('pocConfirm');
  var cancelBtn=document.getElementById('pocCancel');
  var targetForm=null;
  var icons={ ok:'📥', warn:'⚠️', danger:'🗑️' };
  function closeP(){ if(overlay) overlay.classList.remove('open'); }
  if(cancelBtn) cancelBtn.addEventListener('click', closeP);
  if(overlay) overlay.addEventListener('click', function(e){ if(e.target===overlay) closeP(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeP(); });
  document.querySelectorAll('.po-confirm').forEach(function(btn){
    btn.addEventListener('click', function(){
      targetForm=document.getElementById(btn.dataset.form);
      if(titleEl) titleEl.textContent=btn.dataset.title || 'Confirmar?';
      if(textEl) textEl.textContent=btn.dataset.text || '';
      var v=btn.dataset.variant || 'warn';
      if(icoEl) icoEl.textContent=icons[v] || '⚠️';
      confirmBtn.className='btn poc-confirm v-'+v;
      if(overlay) overlay.classList.add('open');
    });
  });
  if(confirmBtn) confirmBtn.addEventListener('click', function(){ if(targetForm) targetForm.submit(); });
})();
</script>
@endsection

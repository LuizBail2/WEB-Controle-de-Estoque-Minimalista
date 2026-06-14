<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color:#1f2937; font-size:12px; }
    .head { border-bottom:2px solid #111827; padding-bottom:10px; margin-bottom:16px; }
    .brand { font-size:20px; font-weight:bold; }
    .doc-title { font-size:14px; margin-top:2px; }
    .meta { color:#6b7280; font-size:11px; margin-top:4px; }
    table.kv { width:100%; border-collapse:collapse; margin-bottom:14px; }
    table.kv td { padding:5px 0; border-bottom:1px solid #e5e7eb; }
    table.kv td.k { color:#6b7280; width:38%; }
    table.items { width:100%; border-collapse:collapse; margin-top:6px; }
    table.items th { text-align:left; background:#f3f4f6; padding:7px 8px; font-size:11px; border-bottom:1px solid #d1d5db; }
    table.items td { padding:7px 8px; border-bottom:1px solid #eee; }
    .right { text-align:right; }
    .total-row td { font-weight:bold; border-top:2px solid #111827; }
    .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:bold; }
    .b-pend { background:#fef3c7; color:#92400e; }
    .b-appr { background:#dcfce7; color:#166534; }
    .b-rej  { background:#fee2e2; color:#991b1b; }
    .foot { margin-top:30px; color:#9ca3af; font-size:10px; text-align:center; }
</style>
</head>
<body>
    <div class="head">
        <div class="brand">Nexo Estoque</div>
        <div class="doc-title">Solicitação de Pedido de Compra</div>
        <div class="meta">Pedido Nº {{ $order->code }} &nbsp;•&nbsp; Emitido em {{ now()->format('d/m/Y \à\s H:i') }}</div>
    </div>

    <table class="kv">
        <tr><td class="k">Fornecedor</td><td>{{ $order->supplier->name ?? '—' }}</td></tr>
        <tr><td class="k">Solicitado por</td><td>{{ optional($order->creator)->name ?? '—' }}</td></tr>
        <tr><td class="k">Data da solicitação</td><td>{{ $order->created_at?->format('d/m/Y \à\s H:i') }}</td></tr>
        <tr>
            <td class="k">Situação de aprovação</td>
            <td>
                @if($order->isApproved())
                    <span class="badge b-appr">Aprovado</span>
                @elseif($order->isRejected())
                    <span class="badge b-rej">Rejeitado</span>
                @else
                    <span class="badge b-pend">Aguardando aprovação</span>
                @endif
            </td>
        </tr>
        @if($order->note)
        <tr><td class="k">Observação</td><td>{{ $order->note }}</td></tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Código</th>
                <th class="right">Quantidade</th>
                <th class="right">Preço unit.</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $it)
                <tr>
                    <td>{{ $it->product->name ?? '—' }}</td>
                    <td>{{ $it->product->code ?? '—' }}</td>
                    <td class="right">{{ $it->quantity }}</td>
                    <td class="right">R$ {{ number_format($it->unit_price ?? 0, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format(($it->unit_price ?? 0) * $it->quantity, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="right">Total</td>
                <td class="right">R$ {{ number_format($order->total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="foot">Documento gerado automaticamente pelo Nexo Estoque — controle de estoque.</div>
</body>
</html>

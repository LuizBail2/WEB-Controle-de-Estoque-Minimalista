<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color:#1f2937; font-size:11px; margin:0; }
        .head { border-bottom:2px solid #3b82f6; padding-bottom:10px; margin-bottom:14px; }
        .head h1 { margin:0; font-size:18px; color:#111827; }
        .head .sub { color:#6b7280; font-size:11px; margin-top:3px; }
        .cards { width:100%; margin-bottom:14px; }
        .cards td { width:25%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:6px; }
        .cards .lbl { color:#6b7280; font-size:10px; }
        .cards .val { font-size:16px; font-weight:bold; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th { background:#111827; color:#fff; text-align:left; padding:6px 8px; font-size:10px; }
        table.data td { padding:5px 8px; border-bottom:1px solid #e5e7eb; }
        table.data tr:nth-child(even) td { background:#f9fafb; }
        .r { text-align:right; }
        .b { display:inline-block; padding:2px 7px; border-radius:10px; font-size:9px; font-weight:bold; }
        .b-ok { background:#dcfce7; color:#15803d; }
        .b-low { background:#fef9c3; color:#a16207; }
        .b-out { background:#fee2e2; color:#b91c1c; }
        .foot { margin-top:12px; color:#9ca3af; font-size:9px; text-align:right; }
    </style>
</head>
<body>
    <div class="head">
        <h1>Relatório de Estoque — Nexo Estoque</h1>
        <div class="sub">Gerado em {{ $generated_at->format('d/m/Y H:i') }}</div>
    </div>

    <table class="cards">
        <tr>
            <td><div class="lbl">Total de produtos</div><div class="val">{{ $stats->total }}</div></td>
            <td><div class="lbl">Em estoque (OK)</div><div class="val">{{ $stats->ok }}</div></td>
            <td><div class="lbl">Estoque baixo</div><div class="val">{{ $stats->baixo }}</div></td>
            <td><div class="lbl">Valor total em estoque</div><div class="val">R$ {{ number_format($stats->valor_total, 2, ',', '.') }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Produto</th>
                <th>SKU</th>
                <th>Categoria</th>
                <th class="r">Qtd</th>
                <th class="r">Mín</th>
                <th class="r">Preço</th>
                <th class="r">Valor Total</th>
                <th>Status</th>
                <th>Fornecedor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $p)
                @php
                    $isOut = $p->quantity == 0;
                    $isLow = $p->quantity > 0 && $p->quantity <= $p->minimum_quantity;
                @endphp
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->code ?: '—' }}</td>
                    <td>{{ $p->category ?: '—' }}</td>
                    <td class="r">{{ $p->quantity }}</td>
                    <td class="r">{{ $p->minimum_quantity }}</td>
                    <td class="r">R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</td>
                    <td class="r">R$ {{ number_format(($p->price ?? 0) * $p->quantity, 2, ',', '.') }}</td>
                    <td>
                        @if($isOut)<span class="b b-out">Sem estoque</span>
                        @elseif($isLow)<span class="b b-low">Baixo</span>
                        @else<span class="b b-ok">OK</span>@endif
                    </td>
                    <td>{{ $p->supplier ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">Nexo Estoque — Relatório gerado automaticamente · {{ $products->count() }} produto(s) listado(s)</div>
</body>
</html>

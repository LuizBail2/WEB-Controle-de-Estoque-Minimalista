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
        .cards td { width:25%; padding:8px 10px; border:1px solid #e5e7eb; }
        .cards .lbl { color:#6b7280; font-size:10px; }
        .cards .val { font-size:15px; font-weight:bold; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th { background:#111827; color:#fff; text-align:left; padding:6px 8px; font-size:10px; }
        table.data td { padding:5px 8px; border-bottom:1px solid #e5e7eb; }
        table.data tr:nth-child(even) td { background:#f9fafb; }
        .r { text-align:right; }
        .foot { margin-top:12px; color:#9ca3af; font-size:9px; text-align:right; }
    </style>
</head>
<body>
    <div class="head">
        <h1>Relatório Financeiro — StockPro</h1>
        <div class="sub">Gerado em {{ $generated_at->format('d/m/Y H:i') }} • Custo médio calculado a partir das entradas</div>
    </div>

    <table class="cards">
        <tr>
            <td><div class="lbl">Estoque (a custo)</div><div class="val">R$ {{ number_format($summary['stock_value_cost'], 2, ',', '.') }}</div></td>
            <td><div class="lbl">Margem média</div><div class="val">{{ number_format($summary['avg_margin'], 1, ',', '.') }}%</div></td>
            <td><div class="lbl">A receber (aberto)</div><div class="val">R$ {{ number_format($summary['receivable_open'], 2, ',', '.') }}</div></td>
            <td><div class="lbl">A pagar (aberto)</div><div class="val">R$ {{ number_format($summary['payable_open'], 2, ',', '.') }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Produto</th>
                <th class="r">Custo médio</th>
                <th class="r">Preço venda</th>
                <th class="r">Lucro un.</th>
                <th class="r">Margem</th>
                <th class="r">Estoque</th>
                <th class="r">Estoque (custo)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td class="r">{{ $p->has_cost ? 'R$ '.number_format($p->avg_cost, 2, ',', '.') : '—' }}</td>
                    <td class="r">R$ {{ number_format($p->price, 2, ',', '.') }}</td>
                    <td class="r">{{ $p->has_cost ? 'R$ '.number_format($p->unit_profit, 2, ',', '.') : '—' }}</td>
                    <td class="r">{{ $p->has_cost ? number_format($p->margin_pct, 1, ',', '.').'%' : '—' }}</td>
                    <td class="r">{{ $p->quantity }}</td>
                    <td class="r">R$ {{ number_format($p->stock_cost, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">StockPro — relatório gerencial. Valores de custo são estimativas baseadas no histórico de compras; não constituem apuração fiscal.</div>
</body>
</html>

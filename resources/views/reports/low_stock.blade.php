<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Relatório - Estoque Baixo</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#222; }
    .header { text-align:center; margin-bottom:12px; }
    .title { font-size:18px; font-weight:700; }
    .meta { font-size:11px; color:#555; margin-top:6px; }
    table { width:100%; border-collapse:collapse; margin-top:12px; }
    th, td { border:1px solid #ddd; padding:6px 8px; text-align:left; vertical-align:top; }
    thead th { background:#f4f4f4; font-weight:700; }
    .right { text-align:right; }
    .small { font-size:11px; color:#666; }
  </style>
</head>
<body>
  <div class="header">
    <div class="title">Relatório — Itens em Estoque Baixo</div>
    <div class="meta">Gerado em: {{ $generated_at->format('d/m/Y H:i') }}</div>
    @if($filters['category'])
      <div class="meta">Filtro: Categoria ID {{ $filters['category'] }}</div>
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:36%">Produto</th>
        <th style="width:18%">Categoria</th>
        <th style="width:10%">Qtd</th>
        <th style="width:10%">Mínimo</th>
        <th style="width:15%">Preço</th>
        <th style="width:11%">Observação</th>
      </tr>
    </thead>
    <tbody>
      @forelse($products as $p)
        <tr>
          <td>{{ $p->name }}</td>
          <td class="small">{{ optional($p->category)->name ?? '-' }}</td>
          <td class="right">{{ $p->quantity }}</td>
          <td class="right">{{ $p->minimum_quantity }}</td>
          <td class="right">{{ $p->price ? 'R$ '.number_format($p->price,2,',','.') : '-' }}</td>
          <td class="small">
            @if($p->quantity <= $p->minimum_quantity)
              Estoque baixo
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center">Nenhum produto em estoque baixo.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:18px; font-size:11px; color:#777;">
    Total de registros: {{ $products->count() }}
  </div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
@php $isTransfer = $m->type === 'transferencia'; @endphp
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color:#1f2937; font-size:12px; margin:0; padding:0; }
    .doc { padding:36px 40px; }
    .head { border-bottom:3px solid {{ $isTransfer ? '#3b82f6' : '#7c5cfc' }}; padding-bottom:14px; margin-bottom:22px; }
    .brand { font-size:20px; font-weight:bold; color:#111827; }
    .brand span { color:#3b82f6; }
    .title { font-size:16px; font-weight:bold; margin-top:6px; color:#111827; }
    .meta { color:#6b7280; font-size:11px; margin-top:4px; }
    table.info { width:100%; border-collapse:collapse; margin-bottom:18px; }
    table.info td { padding:9px 10px; border:1px solid #e5e7eb; vertical-align:top; }
    table.info td.k { background:#f3f4f6; color:#374151; font-weight:bold; width:34%; }
    .badge { display:inline-block; padding:2px 9px; border-radius:10px; font-size:10px; font-weight:bold; }
    .badge-forn { background:#fee2e2; color:#b91c1c; }
    .badge-cli  { background:#dcfce7; color:#15803d; }
    .badge-trf  { background:#dbeafe; color:#1d4ed8; }
    .reason-box { border:1px solid #e5e7eb; border-radius:6px; padding:12px 14px; background:#fafafa; margin-bottom:26px; }
    .reason-box .lbl { font-size:10px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; margin-bottom:6px; }
    .sign { margin-top:50px; }
    .sign .line { border-top:1px solid #9ca3af; width:280px; padding-top:6px; font-size:11px; color:#374151; }
    .foot { margin-top:30px; border-top:1px solid #e5e7eb; padding-top:10px; color:#9ca3af; font-size:10px; text-align:center; }
</style>
</head>
<body>
<div class="doc">

    <div class="head">
        <div class="brand">Nexo<span>Estoque</span></div>
        <div class="title">{{ $isTransfer ? 'Documento de Transferência de Produto' : 'Documento de Devolução de Produto' }}</div>
        <div class="meta">
            Documento Nº {{ $isTransfer ? 'TRF' : 'DEV' }}-{{ str_pad($m->id, 4, '0', STR_PAD_LEFT) }}
            &nbsp;•&nbsp; Emitido em {{ $m->created_at->format('d/m/Y \à\s H:i') }}
        </div>
    </div>

    <table class="info">
        @if($isTransfer)
            <tr>
                <td class="k">Tipo</td>
                <td><span class="badge badge-trf">TRANSFERÊNCIA</span> (saída do estoque desta unidade)</td>
            </tr>
        @else
            <tr>
                <td class="k">Tipo de devolução</td>
                <td>
                    @if($m->direction === 'fornecedor')
                        <span class="badge badge-forn">Devolução AO FORNECEDOR</span> (saída do estoque)
                    @else
                        <span class="badge badge-cli">Devolução DE CLIENTE</span> (entrada no estoque)
                    @endif
                </td>
            </tr>
        @endif

        <tr>
            <td class="k">Produto</td>
            <td>{{ $m->product->name ?? '(produto removido)' }}</td>
        </tr>
        <tr>
            <td class="k">Código / SKU</td>
            <td>{{ $m->product->code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Lote(s)</td>
            <td>{{ $m->lote ?: '—' }}</td>
        </tr>

        @if($isTransfer)
            <tr>
                <td class="k">Destino</td>
                <td>{{ $m->destination ?? '—' }}</td>
            </tr>
            <tr>
                <td class="k">Quantidade transferida</td>
                <td>{{ $m->quantity }} unidade(s)</td>
            </tr>
        @else
            <tr>
                <td class="k">Fornecedor</td>
                <td>{{ $m->product->supplier ?? '—' }}</td>
            </tr>
            <tr>
                <td class="k">Quantidade devolvida</td>
                <td>{{ $m->quantity }} unidade(s)</td>
            </tr>
            <tr>
                <td class="k">Data de entrada do produto</td>
                <td>{{ $m->ref_date ? $m->ref_date->format('d/m/Y') : '—' }}</td>
            </tr>
        @endif

        <tr>
            <td class="k">Responsável</td>
            <td>{{ optional($m->creator)->name ?? optional($m->user)->name ?? '—' }}</td>
        </tr>
    </table>

    @if($isTransfer)
        @if($m->note)
            <div class="reason-box">
                <div class="lbl">Observação</div>
                <div>{{ $m->note }}</div>
            </div>
        @endif
    @else
        <div class="reason-box">
            <div class="lbl">Motivo da devolução</div>
            <div>{{ $m->reason ?: '—' }}</div>
        </div>
    @endif

    <div class="sign">
        <div class="line">Assinatura do responsável</div>
    </div>

    <div class="foot">
        Documento gerado automaticamente pelo Nexo Estoque — controle de estoque.
    </div>

</div>
</body>
</html>

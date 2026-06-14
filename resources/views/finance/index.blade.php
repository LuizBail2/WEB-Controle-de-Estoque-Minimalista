@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold">Financeiro</h2>
            <p class="text-secondary small mb-0">Custos, lucro e posição de caixa</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('finance.cashflow') }}" class="btn btn-outline-light btn-sm">Fluxo de caixa</a>
            <a href="{{ route('finance.payables') }}" class="btn btn-outline-light btn-sm">Contas a pagar</a>
            <a href="{{ route('finance.receivables') }}" class="btn btn-outline-light btn-sm">Contas a receber</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['Estoque (a custo)',  $summary['stock_value_cost'],  'text-info'],
                ['Margem média',       $summary['avg_margin'].'%',    'text-success', true],
                ['A receber (aberto)', $summary['receivable_open'],   'text-success'],
                ['A pagar (aberto)',   $summary['payable_open'],      'text-warning'],
                ['Saldo projetado',    $summary['projected_balance'], $summary['projected_balance'] >= 0 ? 'text-success' : 'text-danger'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="col-6 col-lg">
                <div class="card bg-dark border-secondary-subtle h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase mb-1">{{ $c[0] }}</div>
                        <div class="fs-5 fw-bold {{ $c[2] }}">
                            {{ ($c[3] ?? false) ? $c[1] : 'R$ '.number_format($c[1], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($summary['payable_overdue'] > 0 || $summary['receivable_overdue'] > 0)
        <div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center">
            @if ($summary['payable_overdue'] > 0)
                <span>⚠️ <strong>R$ {{ number_format($summary['payable_overdue'], 2, ',', '.') }}</strong> em contas a pagar vencidas.</span>
            @endif
            @if ($summary['receivable_overdue'] > 0)
                <span>⏳ <strong>R$ {{ number_format($summary['receivable_overdue'], 2, ',', '.') }}</strong> a receber vencido.</span>
            @endif
        </div>
    @endif

    @if ($summary['products_no_cost'] > 0)
        <div class="alert alert-secondary small">
            ℹ️ {{ $summary['products_no_cost'] }} produto(s) ainda sem custo calculado — não têm movimentação de entrada com preço registrado. O custo médio aparece assim que houver uma entrada de compra.
        </div>
    @endif

    <div class="card bg-dark border-secondary-subtle">
        <div class="card-header bg-transparent border-secondary-subtle">
            <h3 class="h6 mb-0">Custo médio, preço e margem por produto</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-dark table-hover mb-0 align-middle fin-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-end">Custo médio</th>
                        <th class="text-end">Preço venda</th>
                        <th class="text-end">Lucro un.</th>
                        <th class="text-end">Margem</th>
                        <th class="text-end">Estoque (custo)</th>
                        <th class="fin-eye"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td data-label="Produto" class="fin-c-prod">{{ $p->name }}</td>
                            <td data-label="Custo médio" class="text-end fin-c-extra">
                                @if ($p->has_cost)
                                    R$ {{ number_format($p->avg_cost, 2, ',', '.') }}
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td data-label="Preço venda" class="text-end fin-c-extra">R$ {{ number_format($p->price, 2, ',', '.') }}</td>
                            <td data-label="Lucro un." class="text-end fin-c-extra">
                                @if ($p->has_cost) R$ {{ number_format($p->unit_profit, 2, ',', '.') }} @else <span class="text-secondary">—</span> @endif
                            </td>
                            <td data-label="Margem" class="text-end fin-c-extra">
                                @if ($p->has_cost)
                                    <span class="badge {{ $p->margin_pct >= 30 ? 'text-bg-success' : ($p->margin_pct >= 10 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                        {{ number_format($p->margin_pct, 1, ',', '.') }}%
                                    </span>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td data-label="Estoque (custo)" class="text-end fin-c-stock">R$ {{ number_format($p->stock_cost, 2, ',', '.') }}</td>
                            <td class="fin-eye">
                                <button type="button" class="fin-eye-btn" title="Ver detalhes">👁</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Nenhum produto cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Coluna do olho só no mobile */
    .fin-eye{ display:none; }
    .fin-eye-btn{ width:34px; height:34px; border-radius:9px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); cursor:pointer; font-size:14px; }

    @media (max-width: 768px){
        .fin-table thead{ display:none; }
        .fin-table, .fin-table tbody{ display:block; width:100%; }
        .fin-table tr{
            display:flex; flex-wrap:wrap; align-items:center; gap:6px 10px;
            background: var(--t-panel-2);
            border:1px solid var(--t-border);
            border-radius:12px; padding:12px 14px; margin-bottom:10px;
        }
        .fin-table td{ border:0 !important; padding:0 !important; background:transparent !important; }
        .fin-table td.fin-c-prod{ order:1; flex:1 1 auto; font-weight:600; text-align:left !important; }
        .fin-table td.fin-c-stock{ order:2; text-align:right !important; font-weight:600; }
        .fin-table td.fin-c-stock::before{ content:'Estoque: '; color:var(--t-muted); font-weight:400; font-size:.76rem; }
        .fin-table td.fin-eye{ order:3; display:flex; }
        .fin-table td.fin-c-extra{
            display:none; order:4; flex:1 1 100%;
            justify-content:space-between; align-items:center; text-align:right !important;
            padding-top:8px !important; border-top:1px solid var(--t-border-soft) !important;
        }
        .fin-table td.fin-c-extra::before{
            content:attr(data-label); color:var(--t-muted);
            font-size:.74rem; text-transform:uppercase; letter-spacing:.03em; font-weight:600;
        }
        .fin-table tr.is-open td.fin-c-extra{ display:flex; }
        .fin-table tr.is-open .fin-eye-btn{ background: rgba(59,130,246,.18); border-color: var(--accent, #3b82f6); }
    }
</style>

<script>
    document.querySelectorAll('.fin-table .fin-eye-btn').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('tr').classList.toggle('is-open'); });
    });
</script>
@endsection
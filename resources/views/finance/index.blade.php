@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold">Financeiro</h2>
            <p class="fin-muted small mb-0">Custos, lucro e posição de caixa</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('finance.cashflow') }}" class="fin-btn-nav">Fluxo de caixa</a>
            <a href="{{ route('finance.payables') }}" class="fin-btn-nav">Contas a pagar</a>
            <a href="{{ route('finance.receivables') }}" class="fin-btn-nav">Contas a receber</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['Estoque (a custo)',  $summary['stock_value_cost'],  'fin-info'],
                ['Margem média',       $summary['avg_margin'].'%',    'fin-pos', true],
                ['A receber (aberto)', $summary['receivable_open'],   'fin-pos'],
                ['A pagar (aberto)',   $summary['payable_open'],      'fin-warn'],
                ['Saldo projetado',    $summary['projected_balance'], $summary['projected_balance'] >= 0 ? 'fin-pos' : 'fin-neg'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="col-6 col-lg">
                <div class="fin-kpi">
                    <div class="fin-kpi__label">{{ $c[0] }}</div>
                    <div class="fin-kpi__value {{ $c[2] }}">
                        {{ ($c[3] ?? false) ? $c[1] : 'R$ '.number_format($c[1], 2, ',', '.') }}
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

    <div class="fin-card">
        <div class="fin-card-head">
            <h3 class="h6">Custo médio, preço e margem por produto</h3>
        </div>
        <div class="fin-card-body p0">
            <table class="fin-tbl align-middle">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="t-end">Custo médio</th>
                        <th class="t-end">Preço venda</th>
                        <th class="t-end">Lucro un.</th>
                        <th class="t-end">Margem</th>
                        <th class="t-end">Estoque (custo)</th>
                        <th class="fin-eye"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td data-label="Produto" class="fin-c-prod">{{ $p->name }}</td>
                            <td data-label="Custo médio" class="t-end fin-c-extra">
                                @if ($p->has_cost)
                                    R$ {{ number_format($p->avg_cost, 2, ',', '.') }}
                                @else
                                    <span class="fin-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Preço venda" class="t-end fin-c-extra">R$ {{ number_format($p->price, 2, ',', '.') }}</td>
                            <td data-label="Lucro un." class="t-end fin-c-extra">
                                @if ($p->has_cost) R$ {{ number_format($p->unit_profit, 2, ',', '.') }} @else <span class="fin-muted">—</span> @endif
                            </td>
                            <td data-label="Margem" class="t-end fin-c-extra">
                                @if ($p->has_cost)
                                    <span class="badge {{ $p->margin_pct >= 30 ? 'text-bg-success' : ($p->margin_pct >= 10 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                        {{ number_format($p->margin_pct, 1, ',', '.') }}%
                                    </span>
                                @else
                                    <span class="fin-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Estoque (custo)" class="t-end fin-c-stock">R$ {{ number_format($p->stock_cost, 2, ',', '.') }}</td>
                            <td class="fin-eye">
                                <button type="button" class="fin-eye-btn" title="Ver detalhes">👁</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center fin-muted py-4">Nenhum produto cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('finance._styles')

<script>
    document.querySelectorAll('.fin-tbl .fin-eye-btn').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('tr').classList.toggle('is-open'); });
    });
</script>
@endsection

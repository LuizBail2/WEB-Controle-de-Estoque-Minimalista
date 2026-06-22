@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0 fw-bold">Fluxo de Caixa</h2>
        <a href="{{ route('finance.index') }}" class="fin-btn-nav">← Financeiro</a>
    </div>

    <div class="row g-3 mb-4">
        @php
            $totCards = [
                ['Entradas', $totals['inflow'],  'fin-pos'],
                ['Saídas',   $totals['outflow'], 'fin-neg'],
                ['Saldo',    $totals['net'],     $totals['net'] >= 0 ? 'fin-pos' : 'fin-neg'],
            ];
        @endphp
        @foreach ($totCards as $c)
            <div class="col-12 col-md-4">
                <div class="fin-kpi">
                    <div class="fin-kpi__label">{{ $c[0] }} ({{ $months }} meses)</div>
                    <div class="fin-kpi__value {{ $c[2] }}">R$ {{ number_format($c[1], 2, ',', '.') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @php $max = max(1, collect($series)->flatMap(fn($m) => [$m['inflow'], $m['outflow']])->max()); @endphp
    <div class="fin-card mb-4">
        <div class="fin-card-body">
            <div class="d-flex justify-content-around align-items-end gap-3" style="height: 220px;">
                @foreach ($series as $m)
                    <div class="text-center flex-fill" style="min-width:0;">
                        <div class="d-flex justify-content-center align-items-end gap-1" style="height: 170px;">
                            <div class="bg-success rounded-top" style="width: 14px; height: {{ ($m['inflow'] / $max) * 100 }}%;" title="Entrada: R$ {{ number_format($m['inflow'], 2, ',', '.') }}"></div>
                            <div class="bg-danger rounded-top"  style="width: 14px; height: {{ ($m['outflow'] / $max) * 100 }}%;" title="Saída: R$ {{ number_format($m['outflow'], 2, ',', '.') }}"></div>
                        </div>
                        <div class="small fin-muted mt-2 text-truncate">{{ $m['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-3 justify-content-center mt-3 small">
                <span><span class="badge text-bg-success">&nbsp;</span> Entradas</span>
                <span><span class="badge text-bg-danger">&nbsp;</span> Saídas</span>
            </div>
        </div>
    </div>

    <div class="fin-card">
        <div class="fin-card-body p0">
            <table class="fin-tbl simple">
                <thead>
                    <tr><th>Mês</th><th class="t-end">Entradas</th><th class="t-end">Saídas</th><th class="t-end">Saldo do mês</th><th class="t-end">Acumulado</th></tr>
                </thead>
                <tbody>
                    @foreach ($series as $m)
                        <tr>
                            <td data-label="Mês">{{ $m['label'] }}</td>
                            <td data-label="Entradas" class="t-end fin-pos">R$ {{ number_format($m['inflow'], 2, ',', '.') }}</td>
                            <td data-label="Saídas" class="t-end fin-neg">R$ {{ number_format($m['outflow'], 2, ',', '.') }}</td>
                            <td data-label="Saldo do mês" class="t-end {{ $m['net'] >= 0 ? 'fin-pos' : 'fin-neg' }}">R$ {{ number_format($m['net'], 2, ',', '.') }}</td>
                            <td data-label="Acumulado" class="t-end {{ $m['running'] >= 0 ? 'fin-pos' : 'fin-neg' }}">R$ {{ number_format($m['running'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('finance._styles')
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 finance">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0 fw-bold">Fluxo de Caixa</h2>
        <a href="{{ route('finance.index') }}" class="btn btn-outline-light btn-sm">← Financeiro</a>
    </div>

    <div class="row g-3 mb-4">
        @php
            $totCards = [
                ['Entradas', $totals['inflow'],  'text-success'],
                ['Saídas',   $totals['outflow'], 'text-danger'],
                ['Saldo',    $totals['net'],     $totals['net'] >= 0 ? 'text-success' : 'text-danger'],
            ];
        @endphp
        @foreach ($totCards as $c)
            <div class="col-12 col-md-4">
                <div class="card bg-dark border-secondary-subtle">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">{{ $c[0] }} ({{ $months }} meses)</div>
                        <div class="fs-5 fw-bold {{ $c[2] }}">R$ {{ number_format($c[1], 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @php $max = max(1, collect($series)->flatMap(fn($m) => [$m['inflow'], $m['outflow']])->max()); @endphp
    <div class="card bg-dark border-secondary-subtle mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-around align-items-end gap-3" style="height: 220px;">
                @foreach ($series as $m)
                    <div class="text-center flex-fill" style="min-width:0;">
                        <div class="d-flex justify-content-center align-items-end gap-1" style="height: 170px;">
                            <div class="bg-success rounded-top" style="width: 14px; height: {{ ($m['inflow'] / $max) * 100 }}%;" title="Entrada: R$ {{ number_format($m['inflow'], 2, ',', '.') }}"></div>
                            <div class="bg-danger rounded-top"  style="width: 14px; height: {{ ($m['outflow'] / $max) * 100 }}%;" title="Saída: R$ {{ number_format($m['outflow'], 2, ',', '.') }}"></div>
                        </div>
                        <div class="small text-secondary mt-2 text-truncate">{{ $m['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-3 justify-content-center mt-3 small">
                <span><span class="badge text-bg-success">&nbsp;</span> Entradas</span>
                <span><span class="badge text-bg-danger">&nbsp;</span> Saídas</span>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-secondary-subtle">
        <div class="card-body p-0">
            <table class="table table-dark table-hover mb-0 responsive-table">
                <thead>
                    <tr><th>Mês</th><th class="text-end">Entradas</th><th class="text-end">Saídas</th><th class="text-end">Saldo do mês</th><th class="text-end">Acumulado</th></tr>
                </thead>
                <tbody>
                    @foreach ($series as $m)
                        <tr>
                            <td data-label="Mês">{{ $m['label'] }}</td>
                            <td data-label="Entradas" class="text-end text-success">R$ {{ number_format($m['inflow'], 2, ',', '.') }}</td>
                            <td data-label="Saídas" class="text-end text-danger">R$ {{ number_format($m['outflow'], 2, ',', '.') }}</td>
                            <td data-label="Saldo do mês" class="text-end {{ $m['net'] >= 0 ? 'text-success' : 'text-danger' }}">R$ {{ number_format($m['net'], 2, ',', '.') }}</td>
                            <td data-label="Acumulado" class="text-end {{ $m['running'] >= 0 ? 'text-success' : 'text-danger' }}">R$ {{ number_format($m['running'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media (max-width: 768px){
        .responsive-table thead{ display:none; }
        .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td{ display:block; width:100%; }
        .responsive-table tr{ background: var(--panel-2,#0b1220); border:1px solid var(--border,rgba(255,255,255,.10)); border-radius:12px; margin-bottom:12px; padding:8px 14px; }
        .responsive-table td{ display:flex; justify-content:space-between; align-items:center; gap:14px; border:0 !important; padding:9px 0 !important; text-align:right !important; background:transparent !important; }
        .responsive-table td + td{ border-top:1px solid var(--border,rgba(255,255,255,.06)) !important; }
        .responsive-table td::before{ content:attr(data-label); color:var(--muted,#94a3b8); font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; text-align:left; flex:0 0 auto; }
    }
</style>
@endsection

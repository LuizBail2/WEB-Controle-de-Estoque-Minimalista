<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Product;        
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
//Confirma no banco: a coluna que marca entrada.

    private const INBOUND_TYPE = 'entrada';

    public function index()
    {
        //Custo médio por produto,média do preço unitário de entrada.
        $avgCosts = DB::table('movements')
            ->where('type', self::INBOUND_TYPE)
            ->where('unit_price', '>', 0)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity * unit_price) / NULLIF(SUM(quantity), 0) AS avg_cost')
            ->pluck('avg_cost', 'product_id');

        $products = Product::query()
            ->select('id', 'name', 'price', 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($avgCosts) {
                $cost   = (float) ($avgCosts[$p->id] ?? 0);
                $price  = (float) $p->price;
                $profit = $price - $cost;
                $margin = $price > 0 ? ($profit / $price) * 100 : 0;

                $p->avg_cost    = round($cost, 2);
                $p->unit_profit = round($profit, 2);
                $p->margin_pct  = round($margin, 1);
                $p->stock_cost  = round($cost * (float) $p->quantity, 2);
                $p->has_cost    = $cost > 0; 
                return $p;
            });

        $withCost = $products->where('has_cost', true);

        $summary = [
            'stock_value_cost'   => round($products->sum('stock_cost'), 2),
            'avg_margin'         => round($withCost->avg('margin_pct') ?? 0, 1),
            'payable_open'       => (float) Payable::open()->sum('amount'),
            'receivable_open'    => (float) Receivable::open()->sum('amount'),
            'payable_overdue'    => (float) Payable::overdue()->sum('amount'),
            'receivable_overdue' => (float) Receivable::overdue()->sum('amount'),
            'products_no_cost'   => $products->where('has_cost', false)->count(),
        ];
        $summary['projected_balance'] = round($summary['receivable_open'] - $summary['payable_open'], 2);

        return view('finance.index', compact('products', 'summary'));
    }

    //fluxo da caixa - Cash flow
    public function cashFlow(Request $request)
    {
        $months = (int) $request->integer('months', 6);
        $months = max(1, min($months, 24));

        $start = now()->startOfMonth()->subMonths($months - 1);

        $inflows = Receivable::received()
            ->whereNotNull('received_at')
            ->whereDate('received_at', '>=', $start)
            ->get(['amount', 'received_at']);

        $outflows = Payable::paid()
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $start)
            ->get(['amount', 'paid_at']);

        $series = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $series[$key] = ['label' => $cursor->translatedFormat('M/Y'), 'inflow' => 0.0, 'outflow' => 0.0];
            $cursor->addMonth();
        }

        foreach ($inflows as $r) {
            $k = Carbon::parse($r->received_at)->format('Y-m');
            if (isset($series[$k])) $series[$k]['inflow'] += (float) $r->amount;
        }
        foreach ($outflows as $p) {
            $k = Carbon::parse($p->paid_at)->format('Y-m');
            if (isset($series[$k])) $series[$k]['outflow'] += (float) $p->amount;
        }

        $running = 0.0;
        foreach ($series as &$m) {
            $m['net'] = round($m['inflow'] - $m['outflow'], 2);
            $running += $m['net'];
            $m['running'] = round($running, 2);
        }
        unset($m);

        $totals = [
            'inflow'  => round(array_sum(array_column($series, 'inflow')), 2),
            'outflow' => round(array_sum(array_column($series, 'outflow')), 2),
        ];
        $totals['net'] = round($totals['inflow'] - $totals['outflow'], 2);

        return view('finance.cashflow', compact('series', 'totals', 'months'));
    }

   //contas a pagar - Payables
 
    public function payables(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $items = Payable::query()
            ->when($filter === 'open',    fn ($q) => $q->open())
            ->when($filter === 'paid',    fn ($q) => $q->paid())
            ->when($filter === 'overdue', fn ($q) => $q->overdue())
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('finance.payables', compact('items', 'filter'));
    }

    public function storePayable(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'due_date'    => ['required', 'date'],
            'category'    => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'notes'       => ['nullable', 'string'],
        ]);

        Payable::create($data);

        return back()->with('ok', 'Conta a pagar criada.');
    }

    public function markPaid(Payable $payable)
    {
        $payable->update(['paid' => true, 'paid_at' => now()->toDateString()]);
        return back()->with('ok', 'Conta marcada como paga.');
    }

    public function destroyPayable(Payable $payable)
    {
        $payable->delete();
        return back()->with('ok', 'Conta removida.');
    }
//contas a receber - receivables

    public function receivables(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $items = Receivable::query()
            ->when($filter === 'open',     fn ($q) => $q->open())
            ->when($filter === 'received', fn ($q) => $q->received())
            ->when($filter === 'overdue',  fn ($q) => $q->overdue())
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('finance.receivables', compact('items', 'filter'));
    }

    public function storeReceivable(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'due_date'    => ['required', 'date'],
            'customer'    => ['nullable', 'string', 'max:255'],
            'category'    => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string'],
        ]);

        Receivable::create($data);

        return back()->with('ok', 'Conta a receber criada.');
    }

    public function markReceived(Receivable $receivable)
    {
        $receivable->update(['received' => true, 'received_at' => now()->toDateString()]);
        return back()->with('ok', 'Conta marcada como recebida.');
    }

    public function destroyReceivable(Receivable $receivable)
    {
        $receivable->delete();
        return back()->with('ok', 'Conta removida.');
    }
}
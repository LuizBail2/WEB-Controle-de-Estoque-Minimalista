<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Batch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = Product::selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= minimum_quantity THEN 1 ELSE 0 END), 0) as baixo,
            COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as sem,
            COALESCE(SUM(COALESCE(price, 0) * quantity), 0) as valor
        ")->first();

        //catalogo
        $query = Product::query();
        if ($request->filled('search')) {
            $query->where(function ($w) use ($request) {
                $w->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $products = $query->orderByDesc('created_at')->take(8)->get();

        $categories     = Product::select('category')
            ->whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category');
        $categoryColors = Category::pluck('color', 'name')->toArray();

    //alertas do estoque
        $alerts = Product::where(function ($q) {
                $q->where('quantity', 0)
                  ->orWhereColumn('quantity', '<=', 'minimum_quantity');
            })
            ->orderBy('quantity')
            ->orderBy('name')
            ->take(6)
            ->get();

    //entradas dos últimos 6 meses
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        $entradas = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $totalMes = Movement::whereIn('type', ['entrada', 'devolucao'])
                ->whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->sum('quantity');
            $entradas[] = ['label' => $meses[$m->month - 1], 'total' => (int) $totalMes];
        }

        //por categoria usando porcentagem
        $porCatRaw = Product::selectRaw("COALESCE(NULLIF(category, ''), 'Sem categoria') as cat, COUNT(*) as total")
            ->groupBy('cat')->orderByDesc('total')->get();
        $somaCat = max($porCatRaw->sum('total'), 1);
        $porCategoria = $porCatRaw->map(function ($row) use ($somaCat) {
            return ['cat' => $row->cat, 'total' => (int) $row->total, 'pct' => round($row->total / $somaCat * 100)];
        });

        //lotes vencidos ou que vencem em até 30 dias (com estoque), mais urgentes primeiro
        $expiringBatches = Batch::with('product')
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'stats', 'products', 'categories', 'categoryColors',
            'alerts', 'entradas', 'porCategoria',
            'expiringBatches'
        ));
    }
}

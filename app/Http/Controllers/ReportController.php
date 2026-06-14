<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Movement;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    //Tela de Relatórios
    public function index(Request $request)
    {
        $stats        = $this->stats();
        $porCategoria = Product::selectRaw("COALESCE(NULLIF(category, ''), 'Sem categoria') as cat, COUNT(*) as total")
            ->groupBy('cat')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $products       = $this->filteredQuery($request)->paginate(10)->withQueryString();
        $categories     = Category::orderBy('name')->pluck('name');
        $categoryColors = Category::pluck('color', 'name')->toArray();

        return view('reports.index', compact('stats', 'porCategoria', 'products', 'categories', 'categoryColors'));
    }

    //Exportar CSV
    public function exportCsv(Request $request)
    {
        //Se vierem ids marcados, exporta só eles;
        //exporta o que estiver filtrado na tela.
        if ($request->filled('ids')) {
            $rows = Product::whereIn('id', (array) $request->ids)->orderBy('name')->get();
        } else {
            $rows = $this->filteredQuery($request)->get();
        }
        $filename = 'relatorio_estoque_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            //Excel reconhecer acentos
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Produto', 'SKU', 'Categoria', 'Quantidade', 'Qtd Minima', 'Preco Unit', 'Valor em Estoque', 'Status', 'Fornecedor'], ';');

            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->name,
                    $p->code,
                    $p->category,
                    $p->quantity,
                    $p->minimum_quantity,
                    number_format($p->price ?? 0, 2, ',', '.'),
                    number_format(($p->price ?? 0) * $p->quantity, 2, ',', '.'),
                    $this->statusLabel($p),
                    $p->supplier,
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    //PDF completo do inventário
    public function pdf(Request $request)
    {
        $data = [
            'products'     => $this->filteredQuery($request)->get(),
            'stats'        => $this->stats(),
            'generated_at' => now(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('relatorio_estoque_' . now()->format('Ymd_His') . '.pdf');
    }

    // PDF de estoque baixo
    public function lowStockPdf(Request $request)
    {
        $products = Product::whereColumn('quantity', '<=', 'minimum_quantity')
            ->orderBy('name')
            ->get();

        $data = [
            'products'     => $products,
            'generated_at' => now(),
            'filters'      => ['category' => $request->category ?? null],
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.low_stock', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('relatorio_estoque_baixo_' . now()->format('Ymd_His') . '.pdf');
    }

    //Auxiliar

    private function stats()
    {
        return Product::selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as sem,
            COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= minimum_quantity THEN 1 ELSE 0 END), 0) as baixo,
            COALESCE(SUM(CASE WHEN quantity > minimum_quantity THEN 1 ELSE 0 END), 0) as ok,
            COALESCE(SUM(COALESCE(price, 0) * quantity), 0) as valor_total,
            COALESCE(SUM(CASE WHEN quantity > minimum_quantity THEN COALESCE(price, 0) * quantity ELSE 0 END), 0) as valor_ok,
            COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= minimum_quantity THEN COALESCE(price, 0) * quantity ELSE 0 END), 0) as valor_baixo
        ")->first();
    }

    private function filteredQuery(Request $request)
    {
        $query = Product::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%")
                  ->orWhere('category', 'like', "%{$q}%")
                  ->orWhere('supplier', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'out') {
                $query->where('quantity', 0);
            } elseif ($request->status === 'low') {
                $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'minimum_quantity');
            } elseif ($request->status === 'ok') {
                $query->whereColumn('quantity', '>', 'minimum_quantity');
            }
        }

        //cabeçalhos clicáveis
        $sort = $request->input('sort', 'name_asc');
        $cols = [
            'name'     => 'name',
            'category' => 'category',
            'quantity' => 'quantity',
            'minimum'  => 'minimum_quantity',
            'price'    => 'price',
            'supplier' => 'supplier',
        ];

        if ($sort === 'value_asc') {
            $query->orderByRaw('(COALESCE(price, 0) * quantity) ASC');
        } elseif ($sort === 'value_desc' || $sort === 'valor_desc') {
            $query->orderByRaw('(COALESCE(price, 0) * quantity) DESC');
        } elseif ($sort === 'price_desc' || $sort === 'preco_desc') {
            $query->orderByDesc('price');
        } elseif ($sort === 'qty_desc') {
            $query->orderByDesc('quantity');
        } elseif (preg_match('/^(\\w+)_(asc|desc)$/', $sort, $m) && isset($cols[$m[1]])) {
            $query->orderBy($cols[$m[1]], $m[2]);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query;
    }

    private function statusLabel(Product $p): string
    {
        if ($p->quantity == 0) {
            return 'Sem estoque';
        }
        return $p->quantity <= $p->minimum_quantity ? 'Baixo' : 'OK';
    }

    public function returns(Request $request)
    {
        $query = Movement::with(['product:id,name,code,supplier', 'user:id,name'])
            ->where('type', 'devolucao')
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                  ->orWhere('reason', 'like', "%{$q}%");
            });
        }

        $returns = $query->paginate(15)->withQueryString();

        return view('reports.returns', compact('returns'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use PDF; // facade from barryvdh/laravel-dompdf

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Gera PDF com produtos em estoque baixo.
     * Accepts optional ?category=ID to filter by category.
     */
    public function lowStockPdf(Request $request)
{
    // consulta produtos com estoque <= mínimo, ordenando apenas por nome
    $query = Product::whereColumn('quantity', '<=', 'minimum_quantity')
                    ->orderBy('name');

    // ignoramos qualquer filtro de category por enquanto (opção A)
    $products = $query->get();

    $data = [
        'products' => $products,
        'generated_at' => now(),
        'filters' => ['category' => $request->category ?? null],
    ];

    // gera PDF (usa a facade do barryvdh/laravel-dompdf)
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.low_stock', $data)
                                      ->setPaper('a4', 'portrait');

    $filename = 'relatorio_estoque_baixo_' . now()->format('Ymd_His') . '.pdf';
    return $pdf->download($filename);
    // se preferir abrir no navegador use ->stream($filename);
    }
}
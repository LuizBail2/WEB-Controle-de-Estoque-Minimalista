<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use PDF; // facade from barryvdh/laravel-dompdf

class ReportController extends Controller
{
    //Gera PDF com produtos em estoque baixo
    public function lowStockPdf(Request $request)
{
    // consulta produtos com estoque <= mínimo, ordenando apenas por nome
    $query = Product::whereColumn('quantity', '<=', 'minimum_quantity')
    ->orderBy('name');

    $products = $query->get();

    $data = [
        'products' => $products,
        'generated_at' => now(),
        'filters' => ['category' => $request->category ?? null],
    ];

    //gera PDF (usa a facade do barryvdh/laravel-dompdf)
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.low_stock', $data)
    ->setPaper('a4', 'portrait');

    $filename = 'relatorio_estoque_baixo_' . now()->format('Ymd_His') . '.pdf';
    return $pdf->download($filename);
    }
}
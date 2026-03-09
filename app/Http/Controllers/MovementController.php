<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $query = Movement::with(['product:id,name,category', 'user:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('product', function ($p) use ($q) {
                $p->where('name', 'like', "%{$q}%")
                  ->orWhere('category', 'like', "%{$q}%");
            });
        }

        $movements = $query->paginate(10)->withQueryString();
        $products = Product::select('id', 'name')->orderBy('name')->get();

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $monthQuery = Movement::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $entradasMes = (clone $monthQuery)
            ->whereIn('type', ['entrada','devolucao'])
            ->sum('quantity');

        $saidasMes = (clone $monthQuery)
            ->whereIn('type', ['saida','transferencia'])
            ->sum('quantity');

        $ajustesMes = (clone $monthQuery)
            ->where('type', 'ajuste')
            ->sum('quantity');

        $saldoLiquido = $entradasMes - $saidasMes;

        return view('movements.index', compact(
            'movements',
            'products',
            'entradasMes',
            'saidasMes',
            'ajustesMes',
            'saldoLiquido'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'=> 'required|in:entrada,saida,ajuste,transferencia,devolucao',
            'product_id'=> 'required|exists:products,id',
            'quantity'=> 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note'=> 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            if ($data['type'] === 'entrada' || $data['type'] === 'devolucao') {
                $product->quantity += $data['quantity'];
            }

            if ($data['type'] === 'saida' || $data['type'] === 'transferencia') {
                if ($product->quantity < $data['quantity']) {
                    abort(422, 'Saída/transferência maior que o estoque disponível.');
                }
                $product->quantity -= $data['quantity'];
            }

            if ($data['type'] === 'ajuste') {
                $product->quantity += $data['quantity'];
            }

            $product->save();

            Movement::create([
                'type' => $data['type'],
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'] ?? null,
                'note' => $data['note'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('movements.index')->with('success', 'Movimentação registrada.');
    }
}
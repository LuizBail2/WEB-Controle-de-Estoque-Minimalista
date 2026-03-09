<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // pesquisa por nome/categoria
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        // filtro categoria
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // filtro status (CORRIGIDO)
        if ($request->filled('status')) {
            if ($request->status === 'out') {
                $query->where('quantity', 0);
            } elseif ($request->status === 'low') {
                $query->where('quantity', '>', 0)
                      ->whereColumn('quantity', '<=', 'minimum_quantity');
            } elseif ($request->status === 'ok') {
                $query->whereColumn('quantity', '>', 'minimum_quantity');
            }
        }

        // categorias para o select
        $categories = Product::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $products = $query->orderBy('name')->paginate(7)->withQueryString();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'category' => 'nullable|string|max:250',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
        ]);

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produto criado com sucesso.');
    }

    public function show(Product $product)
    {
        if (request()->wantsJson()) {
            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'quantity' => $product->quantity,
                'minimum_quantity' => $product->minimum_quantity,
                'price' => $product->price,
                'status' => $product->quantity == 0
                    ? 'Sem estoque'
                    : ($product->quantity <= $product->minimum_quantity ? 'Baixo' : 'OK'),
                'stock_value' => $product->price ? ($product->price * $product->quantity) : null,
                'edit_url' => route('products.edit', $product),
            ]);
        }

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {

        if (request()->boolean('modal')) {
            return view('products.partials.edit-form', compact('product'));
        }

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'category' => 'nullable|string|max:250',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
        ]);

        $product->update($data);

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

        $data = $request->validate([
            'name' => 'required|string|max:250',
            'sku' => 'nullable|string|max:80',
            'category' => 'nullable|string|max:250',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:250',
            'location' => 'nullable|string|max:250',
            'note' => 'nullable|string|max:1000',
]);
            return response()->json(['ok' => true]);
        }

        return redirect()->route('products.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produto excluído.');
    }
}
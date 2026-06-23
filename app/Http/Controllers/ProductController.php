<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        //Cards de topo
        $stats = Product::selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= minimum_quantity THEN 1 ELSE 0 END), 0) as baixo,
            COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as sem,
            COALESCE(SUM(CASE WHEN quantity > minimum_quantity THEN 1 ELSE 0 END), 0) as ok,
            COALESCE(SUM(COALESCE(price, 0) * quantity), 0) as valor
        ")->first();

        //Catálogo com filtros
        $query = Product::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%");
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

        $this->applySort($query, $request->input('sort', 'name_asc'));

        $products = $query->paginate(10)->withQueryString();

        $categories     = Product::select('category')
            ->whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category');
        $allCategories  = Category::orderBy('name')->pluck('name');
        $suppliers      = $this->suppliers();
        $categoryColors = Category::pluck('color', 'name')->toArray();

        return view('products.index', compact(
            'products', 'stats', 'categories', 'allCategories', 'suppliers', 'categoryColors'
        ));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->pluck('name');
        $suppliers  = $this->suppliers();
        return view('products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $request->validate([
            'batch_lote'     => 'nullable|string|max:80',
            'batch_expiry'   => 'nullable|date',
            'batch_quantity' => 'nullable|integer|min:1',
        ]);

        // Regra (Opção C): se há lote inicial, sua quantidade não pode passar do estoque do produto.
        $this->ensureBatchFitsStock(
            stock: (int) $data['quantity'],
            existingBatchesQty: 0,
            newBatchQty: $request->filled('batch_quantity') ? (int) $request->input('batch_quantity') : 0
        );

        if (empty($data['code'])) $data['code'] = null;
        if (!empty($data['category'])) Category::firstOrCreate(['name' => $data['category']]);
        if (!empty($data['supplier'])) Supplier::firstOrCreate(['name' => $data['supplier']]);

        $product = Product::create($data);

        //Lote inicial, cria registro em batches e aparece na Validades e nos detalhes.
        if ($request->filled('batch_expiry') && $request->filled('batch_quantity')) {
            Batch::create([
                'product_id'  => $product->id,
                'lote'        => $request->input('batch_lote'),
                'expiry_date' => $request->input('batch_expiry'),
                'quantity'    => (int) $request->input('batch_quantity'),
                'entry_date'  => now()->toDateString(),
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Produto "' . $data['name'] . '" criado com sucesso.');
    }

    public function show(Product $product)
    {
        if (request()->wantsJson()) {
            return response()->json([
                'id'               => $product->id,
                'name'             => $product->name,
                'code'             => $product->code,
                'category'         => $product->category,
                'quantity'         => $product->quantity,
                'minimum_quantity' => $product->minimum_quantity,
                'price'            => $product->price,
                'supplier'         => $product->supplier,
                'location'         => $product->location,
                'note'             => $product->note,
                'status'           => $product->quantity == 0 ? 'Sem estoque' : ($product->quantity <= $product->minimum_quantity ? 'Baixo' : 'OK'),
                'stock_value'      => $product->price ? ($product->price * $product->quantity) : null,
                'batches'          => Batch::where('product_id', $product->id)
                ->where('quantity', '>', 0)
                    ->orderBy('expiry_date')
                ->get()
                ->map(fn ($b) => [
                'lote'        => $b->lote,
                'expiry_date' => optional($b->expiry_date)->format('d/m/Y'),
                'quantity'    => $b->quantity,
                'status'      => $b->status,]),
                'edit_url'         => route('products.edit', $product),
                'update_url'       => route('products.update', $product),
                'delete_url'       => route('products.destroy', $product),
            ]);
        }
        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->pluck('name');
        $suppliers  = $this->suppliers();

        if (request()->boolean('modal')) {
            return view('products.partials.edit-form', compact('product', 'categories', 'suppliers'));
        }
        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product->id);
        $request->validate([
            'batch_lote'     => 'nullable|string|max:80',
            'batch_expiry'   => 'nullable|date',
            'batch_quantity' => 'nullable|integer|min:1',
        ]);

        // Soma dos lotes JÁ existentes (com saldo) deste produto.
        $existingBatchesQty = (int) Batch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $newStock   = (int) $data['quantity'];
        $newBatchQty = $request->filled('batch_quantity') ? (int) $request->input('batch_quantity') : 0;

        //bloqueio escolhido): a nova quantidade não pode ficar abaixo do que já está rastreado em lotes.
        if ($newStock < $existingBatchesQty) {
            throw ValidationException::withMessages([
                'quantity' => "A quantidade ({$newStock}) não pode ser menor que o total já rastreado em lotes ({$existingBatchesQty}). Ajuste os lotes em Validades antes de reduzir o estoque.",
            ]);
        }

        //lotes existentes + lote novo não podem passar do estoque.
        $this->ensureBatchFitsStock(
            stock: $newStock,
            existingBatchesQty: $existingBatchesQty,
            newBatchQty: $newBatchQty
        );

        if (empty($data['code'])) $data['code'] = null;
        if (!empty($data['category'])) Category::firstOrCreate(['name' => $data['category']]);
        if (!empty($data['supplier'])) Supplier::firstOrCreate(['name' => $data['supplier']]);

        $product->update($data);

        //adicionar um novo lote
        if ($request->filled('batch_expiry') && $request->filled('batch_quantity')) {
            Batch::create([
                'product_id'  => $product->id,
                'lote'        => $request->input('batch_lote'),
                'expiry_date' => $request->input('batch_expiry'),
                'quantity'    => (int) $request->input('batch_quantity'),
                'entry_date'  => now()->toDateString(),
            ]);
        }

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('products.index')->with('success', 'Produto "' . $product->name . '" atualizado.');
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produto "' . $name . '" excluído.');
    }

    //meu auxiliar
    private function ensureBatchFitsStock(int $stock, int $existingBatchesQty, int $newBatchQty): void
    {
        if ($newBatchQty <= 0) {
            return; //sem lote novo, nada a validar aqui
        }

        $totalComLote = $existingBatchesQty + $newBatchQty;

        if ($totalComLote > $stock) {
            $disponivel = max(0, $stock - $existingBatchesQty);
            throw ValidationException::withMessages([
                'batch_quantity' => "A quantidade do lote ({$newBatchQty}) faria os lotes somarem {$totalComLote}, acima do estoque ({$stock}). "
                    . ($existingBatchesQty > 0
                        ? "Já há {$existingBatchesQty} em lotes; você pode adicionar no máximo {$disponivel}."
                        : "O lote não pode ser maior que o estoque."),
            ]);
        }
    }

    private function applySort($query, string $sort): void
    {
        $cols = [
            'name' => 'name', 'category' => 'category', 'quantity' => 'quantity',
            'minimum' => 'minimum_quantity', 'price' => 'price', 'supplier' => 'supplier',
        ];

        if ($sort === 'value_asc') {
            $query->orderByRaw('(COALESCE(price, 0) * quantity) ASC');
        } elseif ($sort === 'value_desc') {
            $query->orderByRaw('(COALESCE(price, 0) * quantity) DESC');
        } elseif (preg_match('/^(\w+)_(asc|desc)$/', $sort, $m) && isset($cols[$m[1]])) {
            $query->orderBy($cols[$m[1]], $m[2]);
        } else {
            $query->orderBy('name', 'asc');
        }
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'             => 'required|string|max:250',
            'category'         => 'nullable|string|max:250',
            'quantity'         => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'price'            => 'nullable|numeric|min:0',
            'code'             => ['nullable', 'string', 'max:50'],
            'supplier'         => 'nullable|string|max:250',
            'location'         => 'nullable|string|max:250',
            'note'             => 'nullable|string|max:1000',
        ]);
    }

    private function suppliers()
{
    return Supplier::orderBy('name')->pluck('name');
}
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        //fornecedor como texto
        $counts = Product::selectRaw('supplier, COUNT(*) as total')
            ->whereNotNull('supplier')->where('supplier', '!=', '')
            ->groupBy('supplier')->pluck('total', 'supplier');

        $all = Supplier::orderBy('name')->get()->map(function ($s) use ($counts) {
            $s->products_count = (int) ($counts[$s->name] ?? 0);
            $s->is_active      = $s->products_count > 0;
            return $s;
        });

        $totalFornecedores = $all->count();
        $comProdutos       = $all->where('is_active', true)->count();

        $list = $all;

        if ($request->filled('search')) {
            $q = mb_strtolower($request->search);
            $list = $list->filter(function ($s) use ($q) {
                return str_contains(mb_strtolower($s->name), $q)
                    || str_contains(mb_strtolower((string) $s->contact_name), $q)
                    || str_contains(mb_strtolower((string) $s->email), $q)
                    || str_contains(mb_strtolower((string) $s->phone), $q);
            });
        }

        $list = $request->sort === 'za'
            ? $list->sortByDesc('name')
            : $list->sortBy('name', SORT_FLAG_CASE | SORT_STRING);

        $list = $list->values();

        $perPage = 12;
        $page = Paginator::resolveCurrentPage('page');
        $list = new LengthAwarePaginator(
            $list->forPage($page, $perPage)->values(),
            $list->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('suppliers.index', compact('list', 'totalFornecedores', 'comProdutos'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (Supplier::where('name', $data['name'])->exists()) {
            return back()->withErrors(['name' => 'Você já tem um fornecedor com esse nome.'])->withInput();
        }

        Supplier::create($data);

        return back()->with('success', 'Fornecedor cadastrado com sucesso.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validateData($request);

        $duplicate = Supplier::where('name', $data['name'])
            ->where('id', '!=', $supplier->id)->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'Já existe outro fornecedor com esse nome.']);
        }

        $supplier->update($data);

        return back()->with('success', 'Fornecedor atualizado.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return back()->with('success', 'Fornecedor excluído.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'         => 'required|string|max:200',
            'contact_name' => 'nullable|string|max:200',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:200',
            'document'     => 'nullable|string|max:50',
            'address'      => 'nullable|string|max:300',
            'note'         => 'nullable|string|max:500',
        ]);
    }
}

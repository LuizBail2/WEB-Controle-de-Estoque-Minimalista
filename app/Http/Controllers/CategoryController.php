<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        //produtos existentes com cada nome de categoria.
        $counts = Product::selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')->where('category', '!=', '')
            ->groupBy('category')->pluck('total', 'category');

        //ve se tem produtos na categoria
        $all = Category::orderBy('name')->get()->map(function ($c) use ($counts) {
            $c->products_count = (int) ($counts[$c->name] ?? 0);
            $c->is_active      = $c->products_count > 0;
            return $c;
        });

        $totalCategorias = $all->count();
        $totalAtivas     = $all->where('is_active', true)->count();

        //filtro
        $cats = $all;

        if ($request->filled('search')) {
            $s = mb_strtolower($request->search);
            $cats = $cats->filter(function ($c) use ($s) {
                return str_contains(mb_strtolower($c->name), $s)
                    || str_contains(mb_strtolower((string) $c->description), $s);
            });
        }

        if ($request->status === 'active') {
            $cats = $cats->filter(fn ($c) => $c->is_active);
        } elseif ($request->status === 'inactive') {
            $cats = $cats->filter(fn ($c) => !$c->is_active);
        }

        $cats = $request->sort === 'za'
            ? $cats->sortByDesc('name')
            : $cats->sortBy('name', SORT_FLAG_CASE | SORT_STRING);

        $cats = $cats->values();

        //paginação
        $perPage = 12;
        $page = Paginator::resolveCurrentPage('page');
        $cats = new LengthAwarePaginator(
            $cats->forPage($page, $perPage)->values(),
            $cats->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('categories.index', compact('cats', 'totalCategorias', 'totalAtivas'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (Category::where('name', $data['name'])->exists()) {
            return back()->withErrors(['name' => 'Você já tem uma categoria com esse nome.'])->withInput();
        }

        Category::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?: '📦',
            'color'       => $data['color'] ?: '#3b82f6',
        ]);

        return back()->with('success', 'Categoria criada com sucesso.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateData($request);

        $duplicate = Category::where('name', $data['name'])
            ->where('id', '!=', $category->id)->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'Já existe outra categoria com esse nome.']);
        }

        $category->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?: ($category->icon ?: '📦'),
            'color'       => $data['color'] ?: $category->color,
        ]);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Categoria excluída.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:16',
            'color'       => 'nullable|string|max:20',
        ]);
    }
}

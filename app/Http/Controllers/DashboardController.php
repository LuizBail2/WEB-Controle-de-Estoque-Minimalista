<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalProducts = Product::count();
        $lowStockCount = Product::whereColumn('quantity', '<=', 'minimum_quantity')->count();
        $outOfStockCount = Product::where('quantity', 0)->count();
        $query = Product::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $products = $query->select('id', 'name', 'category', 'quantity', 'minimum_quantity')
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();

        $categories = Product::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('dashboard', compact('totalProducts', 'lowStockCount', 'outOfStockCount', 'products', 'categories'));
    }
}
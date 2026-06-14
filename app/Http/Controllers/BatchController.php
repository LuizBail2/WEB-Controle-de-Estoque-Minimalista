<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BatchController extends Controller
{
    //Tela de Validades
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all | vencidos | d7 | d30 | d90
        $today  = Carbon::today();

        //KPIs
        $kpis = [
            'vencidos' => Batch::vencidos()->count(),
            'd7'       => Batch::venceEm(7)->count(),
            'd30'      => Batch::venceEm(30)->count(),
            'd90'      => Batch::venceEm(90)->count(),
            'total'    => Batch::comEstoque()->count(),
        ];

        //Lista conforme o filtro
        $query = Batch::with('product')->comEstoque();

        $query = match ($filter) {
            'vencidos' => Batch::with('product')->vencidos(),
            'd7'       => Batch::with('product')->venceEm(7),
            'd30'      => Batch::with('product')->venceEm(30),
            'd90'      => Batch::with('product')->venceEm(90),
            default    => $query,
        };

        $batches  = $query->orderBy('expiry_date')->paginate(15)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name', 'code']);

        //Lotes já existentes por produto
        $rows = Batch::where('batches.quantity', '>', 0)
            ->join('products', 'products.id', '=', 'batches.product_id')
            ->get(['batches.lote as lote', 'products.id as pid', 'products.code as code']);
        $byCode = [];
        $byId   = [];
        foreach ($rows as $r) {
            if ($r->lote === null || $r->lote === '') continue;
            if (!empty($r->code)) $byCode[$r->code][$r->lote] = true;
            $byId[$r->pid][$r->lote] = true;
        }
        $lotesByProduct = [];
        foreach ($products as $p) {
            if (!empty($p->code) && isset($byCode[$p->code])) {
                $lotesByProduct[$p->id] = array_values(array_keys($byCode[$p->code]));
            } elseif (isset($byId[$p->id])) {
                $lotesByProduct[$p->id] = array_values(array_keys($byId[$p->id]));
            }
        }

        return view('batches.index', compact('batches', 'kpis', 'filter', 'products', 'lotesByProduct'));
    }

    //Cadastra o lote
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'expiry_date' => ['required', 'date'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'lote'        => ['nullable', 'string', 'max:80'],
            'entry_date'  => ['nullable', 'date'],
            'note'        => ['nullable', 'string', 'max:255'],
        ]);

        Batch::create($data);

        return back()->with('ok', 'Lote cadastrado.');
    }

    //Remove o lote
    public function update(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'lote'        => 'nullable|string|max:80',
            'expiry_date' => 'required|date',
            'quantity'    => 'required|integer|min:0',
            'entry_date'  => 'nullable|date',
        ]);

        $batch->update($data);

        return back()->with('ok', 'Lote atualizado.');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();
        return back()->with('ok', 'Lote removido.');
    }
}

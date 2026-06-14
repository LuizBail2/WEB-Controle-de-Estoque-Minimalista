<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Product;
use App\Models\Batch;
use App\Models\ActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $query = Movement::with(['product:id,name,category,code', 'user:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($outer) use ($q) {
                $outer->whereHas('product', function ($p) use ($q) {
                    $p->where('name', 'like', "%{$q}%")
                      ->orWhere('category', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
                })
                ->orWhere('note', 'like', "%{$q}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(10)->withQueryString();
        $products = Product::select('id', 'name', 'quantity', 'code', 'category', 'supplier', 'location')
            ->orderBy('name')->get();

        $monthStart = now()->copy()->startOfMonth();
        $monthEnd   = now()->copy()->endOfMonth();
        $lastMonthStart = now()->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = now()->copy()->subMonth()->endOfMonth();

        $monthQuery = Movement::query()->where('approval_status', 'approved')->whereBetween('created_at', [$monthStart, $monthEnd]);
        $lastMonthQuery = Movement::query()->where('approval_status', 'approved')->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);

        $entradasMes = (clone $monthQuery)->whereIn('type', ['entrada','devolucao'])->sum('quantity');
        $saidasMes   = (clone $monthQuery)->whereIn('type', ['saida','transferencia'])->sum('quantity');
        $ajustesMes  = (clone $monthQuery)->where('type', 'ajuste')->sum('quantity');

        $entradasMesPassado = (clone $lastMonthQuery)->whereIn('type', ['entrada', 'devolucao'])->sum('quantity');
        $saidasMesPassado   = (clone $lastMonthQuery)->whereIn('type', ['saida', 'transferencia'])->sum('quantity');

        $percentualEntradas = $entradasMesPassado > 0
            ? (($entradasMes - $entradasMesPassado) / $entradasMesPassado) * 100
            : ($entradasMes > 0 ? 100 : 0);
        $percentualSaidas = $saidasMesPassado > 0
            ? (($saidasMes - $saidasMesPassado) / $saidasMesPassado) * 100
            : ($saidasMes > 0 ? 100 : 0);

        $produtosEntraram = (clone $monthQuery)->whereIn('type', ['entrada', 'devolucao'])->distinct('product_id')->count('product_id');
        $produtosSairam   = (clone $monthQuery)->whereIn('type', ['saida', 'transferencia'])->distinct('product_id')->count('product_id');

        $saldoLiquido = $entradasMes - $saidasMes;

        return view('movements.index', compact(
            'movements', 'products', 'entradasMes', 'saidasMes', 'ajustesMes',
            'saldoLiquido', 'percentualEntradas', 'percentualSaidas',
            'produtosEntraram', 'produtosSairam'
        ));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'type'       => 'required|in:entrada,saida,ajuste,transferencia,devolucao',
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'nullable|numeric|min:0',
            'note'       => 'nullable|string|max:255',
        ];
        if ($type === 'ajuste') {
            $rules['real_quantity'] = 'required|integer|min:0';
        } else {
            $rules['quantity'] = 'required|integer|min:1';
        }
        if ($type === 'transferencia') {
            $rules['destination'] = 'required|string|max:200';
        }
        if ($type === 'devolucao') {
            $rules['direction'] = 'required|in:fornecedor,cliente';
            $rules['reason']    = 'required|string|max:500';
            $rules['ref_date']  = 'nullable|date';
        }
        // Lote (controle por validade) — opcional, usado nas entradas
        $rules['batch_lote']   = 'nullable|string|max:80';
        $rules['batch_expiry'] = 'nullable|date';

        $data = $request->validate($rules);

        // Opção A: transferência/devolução criada por FUNCIONÁRIO fica pendente (não mexe no estoque)
        $needsApproval = in_array($type, ['transferencia', 'devolucao'], true) && !Auth::user()->isAdmin();

        DB::transaction(function () use ($data, $type, $needsApproval) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $movQty = (int) ($data['quantity'] ?? 0);
            $note   = $data['note'] ?? null;
            $extra  = ['destination' => null, 'direction' => null, 'reason' => null, 'ref_date' => null];

            // Campos extras + textos padrão (sem tocar no estoque)
            if ($type === 'transferencia') {
                $extra['destination'] = $data['destination'];
                $note = $note ?: ('Transferência para: ' . $data['destination']);
            } elseif ($type === 'devolucao') {
                $extra['direction'] = $data['direction'];
                $extra['reason']    = $data['reason'];
                $extra['ref_date']  = $data['ref_date'] ?? null;
            }

            // ===== PENDENTE: registra a solicitação SEM efeito no estoque/lotes =====
            if ($needsApproval) {
                // valida o estoque já na solicitação (feedback imediato); revalidado na aprovação
                if ($type === 'transferencia' || ($type === 'devolucao' && ($data['direction'] ?? null) === 'fornecedor')) {
                    $this->ensureStock($product, $movQty);
                }
                Movement::create(array_merge([
                    'type'            => $type,
                    'product_id'      => $product->id,
                    'quantity'        => $movQty,
                    'unit_price'      => $data['unit_price'] ?? null,
                    'note'            => $note,
                    'created_by'      => Auth::id(),
                    'lote'            => $data['batch_lote'] ?? null,    // lote preferido / rótulo (aplicado na aprovação)
                    'batch_expiry'    => $data['batch_expiry'] ?? null,  // validade pretendida (devolução-cliente)
                    'approval_status' => 'pending_approval',
                ], $extra));
                return;
            }

            // ===== IMEDIATO (admin, ou tipos que não exigem aprovação) =====
            switch ($type) {
                case 'entrada':
                    $product->quantity += $movQty;
                    break;

                case 'saida':
                    $this->ensureStock($product, $movQty);
                    $product->quantity -= $movQty;
                    break;

                case 'transferencia':
                    $this->ensureStock($product, $movQty);
                    $product->quantity -= $movQty;
                    break;

                case 'devolucao':
                    if ($data['direction'] === 'fornecedor') {
                        $this->ensureStock($product, $movQty);
                        $product->quantity -= $movQty;
                    } else {
                        $product->quantity += $movQty;
                    }
                    break;

                case 'ajuste':
                    $real = (int) $data['real_quantity'];
                    $old  = (int) $product->quantity;
                    $movQty = abs($real - $old);
                    $product->quantity = $real;
                    $note = $note ?: ("Acerto de inventário: de {$old} para {$real}");
                    break;
            }

            $product->save();

            // ===== Controle por lote (validade) =====
            $loteLabel = null; $batchId = null; $consumption = null;
            $dir   = $extra['direction'] ?? null;
            $isIn  = $type === 'entrada' || ($type === 'devolucao' && $dir === 'cliente');
            $isOut = in_array($type, ['saida', 'transferencia'], true) || ($type === 'devolucao' && $dir === 'fornecedor');

            if ($isIn && !empty($data['batch_expiry']) && $movQty > 0) {
                // Entrada de estoque: cria um lote novo
                $batch = Batch::create([
                    'product_id'  => $product->id,
                    'lote'        => $data['batch_lote'] ?? null,
                    'expiry_date' => $data['batch_expiry'],
                    'quantity'    => $movQty,
                    'entry_date'  => now()->toDateString(),
                ]);
                $batchId   = $batch->id;
                $loteLabel = $batch->lote;
            } elseif ($isOut && $movQty > 0) {
                // Saída: baixa do lote informado (se houver) e o restante por FEFO
                $res         = $this->consumeBatchesFefo($product->id, $movQty, $data['batch_lote'] ?? null);
                $consumption = $res['map'] ?: null;
                $loteLabel   = $res['label'];
            } elseif ($type === 'ajuste') {
                // Ajuste: lote apenas informativo (não mexe nos lotes)
                $loteLabel = $data['batch_lote'] ?? null;
            }

            Movement::create(array_merge([
                'type'              => $type,
                'product_id'        => $product->id,
                'quantity'          => $movQty,
                'unit_price'        => $data['unit_price'] ?? null,
                'note'              => $note,
                'created_by'        => Auth::id(),
                'lote'              => $loteLabel,
                'batch_id'          => $batchId,
                'batch_consumption' => $consumption,
                'approval_status'   => 'approved',
            ], $extra));
        });

        return redirect()->route('movements.index')->with('success', $needsApproval
            ? 'Solicitação enviada para aprovação do administrador.'
            : 'Movimentação registrada com sucesso.');
    }

    public function reverse(Movement $movement)
    {
        if ($movement->reversed_at) {
            return back()->with('error', 'Esta movimentação já foi estornada.');
        }
        if (!$movement->isApproved()) {
            return back()->with('error', 'Só é possível estornar movimentações aprovadas.');
        }
        if ($movement->type === 'ajuste') {
            return back()->with('error', 'Para corrigir um acerto de inventário, faça um novo acerto com a quantidade correta.');
        }

        DB::transaction(function () use ($movement) {
            $product = Product::lockForUpdate()->find($movement->product_id);
            if ($product) {
                $product->quantity -= $this->stockEffect($movement);
                if ($product->quantity < 0) {
                    $product->quantity = 0;
                }
                $product->save();
            }

            // Reverter o efeito nos lotes
            if ($movement->batch_id) {
                // era entrada/devolução-cliente: o lote criado é desfeito
                $b = Batch::find($movement->batch_id);
                if ($b) { $b->quantity = max(0, $b->quantity - (int) $movement->quantity); $b->save(); }
            }
            if (!empty($movement->batch_consumption)) {
                // era saída: devolve a quantidade a cada lote consumido
                foreach ($movement->batch_consumption as $bid => $q) {
                    $b = Batch::find($bid);
                    if ($b) { $b->quantity += (int) $q; $b->save(); }
                }
            }

            $movement->update(['reversed_at' => now()]);
        });

        return back()->with('success', 'Movimentação estornada e estoque restaurado.');
    }

    // ===== Aprovação de movimentação (transferência/devolução) — somente admin =====
    public function approve(Movement $movement)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        if (!$movement->isPendingApproval()) {
            return back()->with('error', 'Esta movimentação não está aguardando aprovação.');
        }

        try {
            DB::transaction(function () use ($movement) {
                $this->applyApprovedMovement($movement);
            });
        } catch (ValidationException $e) {
            // ex.: estoque ficou insuficiente entre a solicitação e a aprovação
            return back()->withErrors($e->errors())->with('error', 'Não foi possível aplicar: ' . implode(' ', $e->errors()['quantity'] ?? ['estoque insuficiente.']));
        }

        $this->notifyMovementRequester($movement, 'aprovou');

        return back()->with('success', 'Movimentação aprovada e aplicada ao estoque.');
    }

    public function reject(Movement $movement)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        if (!$movement->isPendingApproval()) {
            return back()->with('error', 'Esta movimentação não está aguardando aprovação.');
        }

        // Nada foi aplicado no estoque enquanto pendente — basta marcar como rejeitada.
        $movement->update([
            'approval_status' => 'rejected',
            'approved_by'     => Auth::id(),
            'approved_at'     => now(),
        ]);

        $this->notifyMovementRequester($movement, 'rejeitou');

        return back()->with('success', 'Movimentação rejeitada.');
    }

    /**
     * Aplica no estoque uma movimentação que estava pendente (transferência/devolução),
     * espelhando exatamente a lógica imediata do store() para esses tipos.
     */
    private function applyApprovedMovement(Movement $movement): void
    {
        $product = Product::lockForUpdate()->findOrFail($movement->product_id);
        $qty = (int) $movement->quantity;
        $dir = $movement->direction;

        // 1) efeito no estoque (revalidado AGORA — o estoque pode ter mudado)
        if ($movement->type === 'transferencia') {
            $this->ensureStock($product, $qty);
            $product->quantity -= $qty;
        } elseif ($movement->type === 'devolucao') {
            if ($dir === 'fornecedor') {
                $this->ensureStock($product, $qty);
                $product->quantity -= $qty;
            } else { // cliente -> entra no estoque
                $product->quantity += $qty;
            }
        }
        $product->save();

        // 2) lotes
        $isIn  = ($movement->type === 'devolucao' && $dir === 'cliente');
        $isOut = ($movement->type === 'transferencia') || ($movement->type === 'devolucao' && $dir === 'fornecedor');
        $loteLabel = $movement->lote; $batchId = null; $consumption = null;

        if ($isIn && !empty($movement->batch_expiry) && $qty > 0) {
            $batch = Batch::create([
                'product_id'  => $product->id,
                'lote'        => $movement->lote,
                'expiry_date' => $movement->batch_expiry,
                'quantity'    => $qty,
                'entry_date'  => now()->toDateString(),
            ]);
            $batchId   = $batch->id;
            $loteLabel = $batch->lote;
        } elseif ($isOut && $qty > 0) {
            $res         = $this->consumeBatchesFefo($product->id, $qty, $movement->lote);
            $consumption = $res['map'] ?: null;
            $loteLabel   = $res['label'];
        }

        $movement->update([
            'lote'              => $loteLabel,
            'batch_id'          => $batchId,
            'batch_consumption' => $consumption,
            'approval_status'   => 'approved',
            'approved_by'       => Auth::id(),
            'approved_at'       => now(),
        ]);
    }

    private function notifyMovementRequester(Movement $movement, string $verbo): void
    {
        if (!$movement->created_by) {
            return;
        }
        $tipo = $movement->type === 'transferencia' ? 'transferência' : 'devolução';
        $prod = optional($movement->product)->name ?? ('#' . $movement->product_id);
        try {
            ActivityLog::create([
                'owner_id'    => $movement->created_by,
                'actor_id'    => Auth::id(),
                'action'      => $verbo,
                'subject'     => 'sua movimentação',
                'description' => 'O administrador ' . $verbo . ' sua ' . $tipo . ' do produto ' . $prod,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function document(Movement $movement)
    {
        if (!in_array($movement->type, ['devolucao', 'transferencia'])) {
            abort(404);
        }
        $movement->load('product', 'user');

        $pdf = Pdf::loadView('movements.document', ['m' => $movement]);
        $prefix = $movement->type === 'transferencia' ? 'transferencia' : 'devolucao';
        return $pdf->stream($prefix . '-' . $movement->id . '.pdf');
    }

    public function export(Request $request)
    {
        $query = Movement::with(['product:id,name,code', 'user:id,name'])->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($outer) use ($q) {
                $outer->whereHas('product', function ($p) use ($q) {
                    $p->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                })->orWhere('note', 'like', "%{$q}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $rows = $query->get();

        $labels = [
            'entrada' => 'Entrada', 'saida' => 'Saída', 'ajuste' => 'Ajuste',
            'transferencia' => 'Transferência', 'devolucao' => 'Devolução',
        ];
        $filename = 'movimentacoes-' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($rows, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 (acentos no Excel)
            fputcsv($out, ['Data/Hora', 'Tipo', 'Produto', 'Código', 'Quantidade', 'Valor Unit.', 'Responsável', 'Observação'], ';');
            foreach ($rows as $m) {
                fputcsv($out, [
                    $m->created_at->format('d/m/Y H:i'),
                    $labels[$m->type] ?? $m->type,
                    $m->product->name ?? '(removido)',
                    $m->product->code ?? '',
                    $m->quantity,
                    $m->unit_price !== null ? number_format($m->unit_price, 2, ',', '.') : '',
                    $m->user->name ?? '',
                    $m->note ?? '',
                ], ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function ensureStock(Product $product, int $qty): void
    {
        if ($product->quantity < $qty) {
            throw ValidationException::withMessages([
                'quantity' => "Estoque insuficiente para \"{$product->name}\": disponível {$product->quantity} unidade(s), você tentou retirar {$qty}.",
            ]);
        }
    }

    private function stockEffect(Movement $m): int
    {
        return match ($m->type) {
            'entrada'                 => (int) $m->quantity,
            'saida', 'transferencia'  => -(int) $m->quantity,
            'devolucao'               => $m->direction === 'fornecedor' ? -(int) $m->quantity : (int) $m->quantity,
            default                   => 0,
        };
    }

    // Baixa de estoque por lote no método FEFO ele consome dos lotes que vencem primeiro. Se produto não tiver lotes cadastrados, retorna vazio.
    
    private function consumeBatchesFefo(int $productId, int $qty, ?string $preferredLote = null): array
    {
        $remaining = $qty;
        $map = [];
        $labels = [];

        $query = Batch::where('product_id', $productId)
            ->where('quantity', '>', 0);

        //Lote informado primeiro ( segundo o padrão do FEFO)
        if (!empty($preferredLote)) {
            $query->orderByRaw('CASE WHEN lote = ? THEN 0 ELSE 1 END', [$preferredLote]);
        }

        $batches = $query->orderBy('expiry_date')->lockForUpdate()->get();

        foreach ($batches as $b) {
            if ($remaining <= 0) break;
            $take = min($remaining, (int) $b->quantity);
            $b->quantity -= $take;
            $b->save();
            $map[$b->id] = $take;
            $labels[] = $b->lote ?: ('#' . $b->id);
            $remaining -= $take;
        }

        return ['map' => $map, 'label' => $labels ? implode(', ', $labels) : null];
    }
}

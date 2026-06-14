<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('supplier')->withCount('items')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        //total por pedido
        $orders->getCollection()->load('items');

        return view('purchase_orders.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'code', 'price', 'quantity']);

        return view('purchase_orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'         => 'required|exists:suppliers,id',
            'note'                => 'nullable|string|max:500',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
        ], [
            'items.required' => 'Adicione pelo menos um produto ao pedido.',
            'supplier_id.required' => 'Selecione o fornecedor.',
        ]);

        DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id'     => $data['supplier_id'],
                'code'            => $this->nextCode(),
                'status'          => 'pendente',
                'note'            => $data['note'] ?? null,
                'created_by'      => auth()->id(),
                'approval_status' => auth()->user()->isAdmin() ? 'approved' : 'pending_approval',
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Pedido de compra criado.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product']);
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    //recebimento que dá entrada no estoque
    public function receive(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pendente') {
            return back()->with('error', 'Este pedido não está pendente.');
        }
        if (!$purchaseOrder->isApproved()) {
            return back()->with('error', 'Este pedido ainda não foi aprovado pelo administrador.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->load('items');

            foreach ($purchaseOrder->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (!$product) {
                    continue;
                }

                //soma no estoque
                $product->quantity += $item->quantity;

                //atualiza o preço de custo do produto com o preço do pedido
                if ($item->unit_price !== null) {
                    $product->price = $item->unit_price;
                }
                $product->save();

                //registra a movimentação de entrada
                Movement::create([
                    'type'       => 'entrada',
                    'product_id' => $product->id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'note'       => 'Recebimento do pedido ' . $purchaseOrder->code,
                    'user_id'    => auth()->user()->ownerId(),
                    'created_by' => auth()->id(),
                ]);
            }

            $purchaseOrder->update([
                'status'      => 'recebido',
                'received_at' => now(),
            ]);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Pedido recebido! As entradas foram lançadas no estoque.');
    }

    //Aprovação
    public function approve(PurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        if (!$purchaseOrder->isPendingApproval()) {
            return back()->with('error', 'Este pedido não está aguardando aprovação.');
        }

        $purchaseOrder->update([
            'approval_status' => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        $this->notifyRequester($purchaseOrder, 'aprovou');

        return back()->with('success', 'Pedido ' . $purchaseOrder->code . ' aprovado.');
    }

    public function reject(PurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        if (!$purchaseOrder->isPendingApproval()) {
            return back()->with('error', 'Este pedido não está aguardando aprovação.');
        }

        $purchaseOrder->update([
            'approval_status' => 'rejected',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        $this->notifyRequester($purchaseOrder, 'rejeitou');

        return back()->with('success', 'Pedido ' . $purchaseOrder->code . ' rejeitado.');
    }

    //Notifica o funcionario que solicitou
    private function notifyRequester(PurchaseOrder $purchaseOrder, string $verbo): void
    {
        if (!$purchaseOrder->created_by) {
            return;
        }
        try {
            \App\Models\ActivityLog::create([
                'owner_id'    => $purchaseOrder->created_by,
                'actor_id'    => auth()->id(),
                'action'      => $verbo,
                'subject'     => 'seu pedido de compra',
                'description' => 'O administrador ' . $verbo . ' seu pedido de compra: ' . $purchaseOrder->code,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    //PDF do pedido (solicitação)
    public function reportPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'creator']);
        $pdf = Pdf::loadView('purchase_orders.report', ['order' => $purchaseOrder]);
        return $pdf->stream('pedido-' . $purchaseOrder->code . '.pdf');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pendente') {
            return back()->with('error', 'Só é possível cancelar um pedido pendente.');
        }
        $purchaseOrder->update(['status' => 'cancelado']);
        return back()->with('success', 'Pedido cancelado.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')->with('success', 'Pedido excluído.');
    }

    private function nextCode(): string
    {
        $n = PurchaseOrder::count() + 1; //já isolado por usuário, trait
        return 'PC-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}

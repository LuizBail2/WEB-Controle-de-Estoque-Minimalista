<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use BelongsToUser, LogsTeamActivity;

    protected $fillable = [
        'product_id', 'user_id', 'type', 'quantity', 'unit_price', 'note',
        'destination', 'direction', 'reason', 'ref_date', 'reversed_at',
        'lote', 'batch_id', 'batch_consumption', 'created_by',
        'approval_status', 'approved_by', 'approved_at', 'batch_expiry',
    ];

    protected $casts = [
        'ref_date'          => 'date',
        'reversed_at'       => 'datetime',
        'batch_consumption' => 'array',
        'approved_at'       => 'datetime',
        'batch_expiry'      => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool        { return $this->approval_status === 'approved'; }
    public function isPendingApproval(): bool { return $this->approval_status === 'pending_approval'; }
    public function isRejected(): bool        { return $this->approval_status === 'rejected'; }

    //Rótulo legível do tipo de movimentação.
     
    public function tipoLabel(): string
    {
        return [
            'entrada'       => 'Entrada',
            'saida'         => 'Saída',
            'ajuste'        => 'Ajuste de inventário',
            'transferencia' => 'Transferência',
            'devolucao'     => 'Devolução',
        ][$this->type] ?? $this->type;
    }

    //Texto usado no titulo
     
    public function activityDisplayName(): string
    {
        $nome = $this->tipoLabel();

        // Devolução: especifica a direção
        if ($this->type === 'devolucao' && !empty($this->direction)) {
            $nome .= $this->direction === 'fornecedor' ? ' (para o fornecedor)' : ' (do cliente)';
        }

        return $nome;
    }

    public function activityMailDetails(): array
    {
        $this->loadMissing('product');

        $details = [
            'Tipo'       => $this->tipoLabel(),
            'Produto'    => $this->product->name ?? '(produto removido)',
            'Código'     => $this->product->code ?? '—',
            'Quantidade' => $this->quantity,
            'Data/Hora'  => optional($this->created_at)->format('d/m/Y H:i'),
        ];

        // Devolução: direção (fornecedor ou cliente) + motivo
        if ($this->type === 'devolucao') {
            if (!empty($this->direction)) {
                $details['Direção'] = $this->direction === 'fornecedor'
                    ? 'Para o fornecedor'
                    : 'Do cliente';
            }
            if (!empty($this->reason)) {
                $details['Motivo'] = $this->reason;
            }
        }

        // Transferência: destino
        if ($this->type === 'transferencia' && !empty($this->destination)) {
            $details['Destino'] = $this->destination;
        }

        if (!empty($this->unit_price)) {
            $details['Valor unitário'] = 'R$ ' . number_format((float) $this->unit_price, 2, ',', '.');
        }
        if (!empty($this->lote)) {
            $details['Lote'] = $this->lote;
        }
        if (!empty($this->note)) {
            $details['Observação'] = $this->note;
        }

        if (in_array($this->type, ['transferencia', 'devolucao'], true)) {
            $details['__pdf_anexo'] = true;
        }

        return $details;
    }
}

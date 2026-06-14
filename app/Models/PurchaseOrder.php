<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use BelongsToUser, LogsTeamActivity;

    protected $fillable = [
        'user_id', 'created_by', 'supplier_id', 'code', 'status', 'note', 'received_at',
        'approval_status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    //Total do pedido soma de (quantidade x preço unitário) dos itens.
    public function getTotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->quantity * ($i->unit_price ?? 0));
    }
}

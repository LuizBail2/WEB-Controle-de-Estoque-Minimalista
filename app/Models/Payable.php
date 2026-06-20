<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payable extends Model
{
    use BelongsToUser, LogsTeamActivity;
    //Eloquent mapeia o contas a pagar automaticamente.
    protected $fillable = [
        'description', 'supplier_id', 'purchase_order_id', 'category',
        'amount', 'due_date', 'paid', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'due_date' => 'date',
        'paid'     => 'boolean',
        'paid_at'  => 'date',
    ];

    public function supplier(): BelongsTo
    {

        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! $this->paid && $this->due_date?->isPast();
    }


    public function scopeOpen($q)    { return $q->where('paid', false); }
    public function scopePaid($q)    { return $q->where('paid', true); }
    public function scopeOverdue($q)
    {
        return $q->where('paid', false)->whereDate('due_date', '<', now());
    }
    use BelongsToUser;
}

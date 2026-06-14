<?php

namespace App\Models;

use App\Models\Concerns\LogsTeamActivity;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    use LogsTeamActivity;
    // Eloquent mapeia contas a receber automaticamente

    protected $fillable = [
        'description', 'customer', 'category',
        'amount', 'due_date', 'received', 'received_at', 'notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'due_date'    => 'date',
        'received'    => 'boolean',
        'received_at' => 'date',
    ];

    public function getIsOverdueAttribute(): bool
    {
        return ! $this->received && $this->due_date?->isPast();
    }

    public function scopeOpen($q)      { return $q->where('received', false); }
    public function scopeReceived($q)  { return $q->where('received', true); }
    public function scopeOverdue($q)
    {
        return $q->where('received', false)->whereDate('due_date', '<', now());
    }
}

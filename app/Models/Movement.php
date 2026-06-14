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
}
<?php

namespace App\Models;

use App\Models\Concerns\LogsTeamActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Batch extends Model
{
    use LogsTeamActivity;
    protected $fillable = [
        'product_id', 'lote', 'expiry_date', 'quantity', 'entry_date', 'note',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'entry_date'  => 'date',
        'quantity'    => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    //Dias até vencer
    public function getDaysLeftAttribute(): int
    {
        return (int) Carbon::today()->diffInDays(Carbon::parse($this->expiry_date)->startOfDay(), false);
    }

    //Status
    public function getStatusAttribute(): string
    {
        $d = $this->days_left;
        if ($d < 0)   return 'vencido';
        if ($d <= 7)  return 'd7';
        if ($d <= 30) return 'd30';
        if ($d <= 90) return 'd90';
        return 'ok';
    }

    //consideram apenas lotes com quantidade
    public function scopeComEstoque($q) { return $q->where('quantity', '>', 0); }

    public function scopeVencidos($q)
    {
        return $q->where('quantity', '>', 0)->whereDate('expiry_date', '<', Carbon::today());
    }

    //Vencem dentro de N dias
    public function scopeVenceEm($q, int $dias)
    {
        return $q->where('quantity', '>', 0)
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays($dias));
    }
}

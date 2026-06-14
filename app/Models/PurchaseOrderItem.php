<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'purchase_order_id', 'product_id', 'quantity', 'unit_price',
    ];

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

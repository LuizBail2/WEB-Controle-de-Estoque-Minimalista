<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, BelongsToUser, LogsTeamActivity;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'category',
        'quantity',
        'minimum_quantity',
        'price',
        'supplier',
        'location',
        'note',
    ];

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function batches()
    {
    return $this->hasMany(\App\Models\Batch::class);
    }

    public function activityMailDetails(): array
    {
        $statusEstoque = $this->quantity == 0
            ? 'Sem estoque'
            : ($this->quantity <= $this->minimum_quantity ? 'Estoque baixo' : 'OK');

        $details = [
            'Produto'      => $this->name,
            'Código'       => $this->code ?: '—',
            'Categoria'    => $this->category ?: '—',
            'Fornecedor'   => $this->supplier ?: '—',
            'Localização'  => $this->location ?: '—',
            'Quantidade'   => $this->quantity,
            'Estoque mín.' => $this->minimum_quantity,
            'Situação'     => $statusEstoque,
        ];

        if (!empty($this->price)) {
            $details['Preço'] = 'R$ ' . number_format((float) $this->price, 2, ',', '.');
        }
        if (!empty($this->note)) {
            $details['Observação'] = $this->note;
        }

        return $details;
    }
}
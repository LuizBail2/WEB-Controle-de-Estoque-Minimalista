<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'cnpj',
        'owner_user_id',
        'plan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            //CNPJ criptografado
            'cnpj' => 'encrypted',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    //CNPJ formatado
    public function cnpjFormatado(): ?string
    {
        $d = preg_replace('/\D/', '', (string) $this->cnpj);
        if (strlen($d) !== 14) {
            return $this->cnpj ?: null;
        }
        return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3)
             . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
    }
}

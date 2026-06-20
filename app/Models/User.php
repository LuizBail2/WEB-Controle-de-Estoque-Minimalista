<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    //Abas do sistema
    public const ABAS = [
        'dashboard'       => 'Dashboard',
        'products'        => 'Produtos',
        'movements'       => 'Movimentações',
        'purchase_orders' => 'Pedidos de Compra',
        'categories'      => 'Categorias',
        'suppliers'       => 'Fornecedores',
        'reports'         => 'Relatórios',
        'finance'         => 'Financeiro',
        'validades'       => 'Validades',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'owner_id',
        'permissions',
        'status',
        'theme',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'permissions'       => 'array',
        ];
    }

    //dono da conta
    public function isAdmin(): bool
    {
        return is_null($this->owner_id);
    }

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    //ID do dono dos dados
    public function ownerId(): int
    {
        return $this->owner_id ?? $this->id;
    }

    //Dono do funcionario
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    //Funcionários deste admin
    public function employees()
    {
        return $this->hasMany(User::class, 'owner_id');
    }


    //premissões defuncionário
    public function hasPermission(string $aba): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return in_array($aba, $this->permissions ?? [], true);
    }

    //primeira aba liberada
    public function firstAllowedAba(): ?string
    {
        if ($this->isAdmin()) {
            return 'dashboard';
        }
        foreach (array_keys(self::ABAS) as $aba) {
            if ($this->hasPermission($aba)) {
                return $aba;
            }
        }
        return null;
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }
}

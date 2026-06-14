<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    //Abas do sistema, usadas nas permissões
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

    // Dono,Equipe

    //É o dono da conta admin
    public function isAdmin(): bool
    {
        return is_null($this->owner_id);
    }

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    //ID do dono dos dados
    public function ownerId(): int
    {
        return $this->owner_id ?? $this->id;
    }

    //admin deste funcionário
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    //Funcionários deste admin
    public function employees()
    {
        return $this->hasMany(User::class, 'owner_id');
    }

    //Permissões

    // O funcionário naõ pode acessar a aba, Admin sempre pode
    public function hasPermission(string $aba): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return in_array($aba, $this->permissions ?? [], true);
    }

    //Primeira aba liberada redireciona funcionário sem acesso ao dashboard
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

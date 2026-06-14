<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

//Isolamento por DONO da conta O dono é o admin. Funcionários compartilham  os dados do dono.
 
trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            if (Auth::check()) {
                $ownerId = Auth::user()->ownerId();
                $builder->where($builder->getModel()->qualifyColumn('user_id'), $ownerId);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && empty($model->user_id)) {
                $model->user_id = Auth::user()->ownerId();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbaPermission
{
    //prefixo do nome da rota
    private array $map = [
        'dashboard'        => 'dashboard',
        'products.'        => 'products',
        'movements.'       => 'movements',
        'purchase-orders.' => 'purchase_orders',
        'categories.'      => 'categories',
        'suppliers.'       => 'suppliers',
        'reports.'         => 'reports',
        'finance.'         => 'finance',
        'batches.'         => 'validades',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        //sem usuário ou admin,libera tudo.
        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        $name = optional($request->route())->getName() ?? '';

        //perfil acessivel
        if (str_starts_with($name, 'profile')) {
            return $next($request);
        }

        $aba = null;
        foreach ($this->map as $prefix => $key) {
            if ($name === rtrim($prefix, '.') || str_starts_with($name, $prefix)) {
                $aba = $key;
                break;
            }
        }
        if ($aba === null) {
            return $next($request);
        }

        if ($user->hasPermission($aba)) {
            return $next($request);
        }

        //manda para a primeira aba liberada, ou pro perfil se não tiver nenhuma.
        $first = $user->firstAllowedAba();
        if ($first && $first !== $aba) {
            return redirect()->route($this->routeNameFor($first))
                ->with('error', 'Você não tem permissão para acessar essa área.');
        }

        return redirect()->route('profile.edit')
            ->with('error', 'Você ainda não tem nenhuma aba liberada. Fale com o administrador.');
    }

    private function routeNameFor(string $aba): string
    {
        return match ($aba) {
            'dashboard'       => 'dashboard',
            'products'        => 'products.index',
            'movements'       => 'movements.index',
            'purchase_orders' => 'purchase-orders.index',
            'categories'      => 'categories.index',
            'suppliers'       => 'suppliers.index',
            'reports'         => 'reports.index',
            'finance'         => 'finance.index',
            'validades'       => 'batches.index',
            default           => 'profile.edit',
        };
    }
}

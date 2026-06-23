<?php

namespace App\Support;

class Mask
{
    //mascara
    public static function email(?string $email, int $visible = 2): string
    {
        $email = (string) $email;

        //Sem @ válido: devolve mascarado genérico para não vazar nada.
        if (!str_contains($email, '@')) {
            return $email === '' ? '' : str_repeat('*', max(3, mb_strlen($email)));
        }

        [$user, $domain] = explode('@', $email, 2);
        $len = mb_strlen($user);

        if ($len <= 1) {
            $maskedUser = '*';
        } elseif ($len <= $visible) {
            //usuário curto: mostra 1, mascara o resto
            $maskedUser = mb_substr($user, 0, 1) . str_repeat('*', $len - 1);
        } else {
            $maskedUser = mb_substr($user, 0, $visible) . str_repeat('*', $len - $visible);
        }

        return $maskedUser . '@' . $domain;
    }
}

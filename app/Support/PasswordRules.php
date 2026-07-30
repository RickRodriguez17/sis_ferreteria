<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function strong(): Password
    {
        // uncompromised() consulta un servicio externo; se omite para validar sin red.
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}

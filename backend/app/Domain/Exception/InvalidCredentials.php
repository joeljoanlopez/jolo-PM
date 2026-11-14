<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** Contraseña, código TOTP, código de recuperación o código de configuración incorrectos. */
final class InvalidCredentials extends DomainException
{
    public static function password(): self
    {
        return new self(new Message('auth.invalid_password'));
    }

    public static function secondFactor(): self
    {
        return new self(new Message('auth.invalid_code'));
    }

    public static function setupCode(): self
    {
        return new self(new Message('auth.invalid_setup_code'));
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Port;

/**
 * Cuenta intentos fallidos por clave (paso + IP) en una ventana de tiempo.
 * Los intentos correctos no cuentan.
 */
interface LoginAttemptLimiter
{
    /** Segundos que faltan para poder volver a intentarlo, o null si se puede ya. */
    public function blockedFor(string $key): ?int;

    public function recordFailure(string $key): void;

    public function clear(string $key): void;
}

<?php

declare(strict_types=1);

namespace App\Domain\Port;

/**
 * Cuenta los intentos fallidos por clave (acción + IP) en una ventana de tiempo.
 * El límite y la ventana los decide el adaptador.
 */
interface LoginAttemptLimiter
{
    /** Segundos que faltan para poder volver a intentarlo, o null si se puede ya. */
    public function retryAfter(string $key): ?int;

    /** Apunta un intento fallido. */
    public function hit(string $key): void;

    public function clear(string $key): void;
}

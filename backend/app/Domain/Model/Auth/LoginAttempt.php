<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

/**
 * Un intento de inicio de sesión para el registro. Fallido si falla cualquiera
 * de los dos pasos; correcto solo cuando pasa el segundo factor.
 */
final class LoginAttempt
{
    public function __construct(
        public readonly string $ip,
        public readonly bool $succeeded,
        public readonly LoginStep $step,
        public readonly \DateTimeImmutable $at,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/** Un intento de inicio de sesión, correcto o no, para el registro. */
final class LoginAttempt
{
    public const PASSWORD = 'password';
    public const TOTP = 'totp';
    public const RECOVERY_CODE = 'recovery_code';

    public function __construct(
        public readonly string $ip,
        /** Qué se comprobó: PASSWORD, TOTP o RECOVERY_CODE. */
        public readonly string $method,
        public readonly bool $success,
        public readonly \DateTimeImmutable $at,
    ) {
    }
}

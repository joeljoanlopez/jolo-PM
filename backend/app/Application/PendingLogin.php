<?php

declare(strict_types=1);

namespace App\Application;

/** Contraseña correcta, falta el segundo factor antes de `expiresAt`. */
final class PendingLogin
{
    public const TTL_SECONDS = 300;

    public function __construct(
        public readonly string $accountId,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Port;

/** Cifrado reversible para guardar el secreto TOTP sin dejarlo en claro. */
interface SecretCipher
{
    public function encrypt(string $plain): string;

    public function decrypt(string $encrypted): string;
}

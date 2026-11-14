<?php

declare(strict_types=1);

namespace App\Domain\Port;

/** Cifrado simétrico para secretos que hay que poder leer (el TOTP). */
interface SecretCipher
{
    public function encrypt(string $plain): string;

    public function decrypt(string $cipher): string;
}

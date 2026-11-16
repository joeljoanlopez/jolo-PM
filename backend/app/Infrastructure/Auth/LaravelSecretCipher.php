<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\SecretCipher;
use Illuminate\Contracts\Encryption\StringEncrypter;

/** Cifrado de Laravel con APP_KEY (AES-256-CBC con MAC). Cambiar la clave obliga a `auth:reset`. */
final class LaravelSecretCipher implements SecretCipher
{
    public function __construct(private readonly StringEncrypter $encrypter)
    {
    }

    public function encrypt(string $plain): string
    {
        return $this->encrypter->encryptString($plain);
    }

    public function decrypt(string $encrypted): string
    {
        return $this->encrypter->decryptString($encrypted);
    }
}

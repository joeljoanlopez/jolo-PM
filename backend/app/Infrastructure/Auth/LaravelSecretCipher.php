<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\SecretCipher;
use Illuminate\Contracts\Encryption\Encrypter;

/** AES-256 con APP_KEY. Si cambia APP_KEY, el secreto TOTP ya no se puede leer. */
final class LaravelSecretCipher implements SecretCipher
{
    public function __construct(private readonly Encrypter $encrypter)
    {
    }

    public function encrypt(string $plain): string
    {
        return $this->encrypter->encryptString($plain);
    }

    public function decrypt(string $cipher): string
    {
        return $this->encrypter->decryptString($cipher);
    }
}

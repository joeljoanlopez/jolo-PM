<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\PasswordHasher;
use Illuminate\Contracts\Hashing\Hasher;

/** El hash de Laravel (bcrypt por defecto; argon2id con HASH_DRIVER=argon2id). */
final class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(private readonly Hasher $hasher)
    {
    }

    public function hash(string $password): string
    {
        return $this->hasher->make($password);
    }

    public function verify(string $password, string $hash): bool
    {
        return $this->hasher->check($password, $hash);
    }
}

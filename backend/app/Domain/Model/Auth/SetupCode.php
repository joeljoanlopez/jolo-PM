<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

use App\Domain\Service\Auth\RandomCode;

/**
 * Código de un solo uso para la configuración inicial. Se escribe en los logs
 * del servidor, así que quien lo tiene ha tenido acceso al servidor. Se guarda
 * solo su hash.
 */
final class SetupCode
{
    public function __construct(public readonly string $hash)
    {
    }

    /** @return array{self, string} el código guardable y el código en claro */
    public static function issue(): array
    {
        $plain = RandomCode::generate(4, 4);

        return [new self(RandomCode::hash($plain)), $plain];
    }

    public function matches(string $code): bool
    {
        return hash_equals($this->hash, RandomCode::hash($code));
    }
}

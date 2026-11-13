<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/**
 * Código de un solo uso para crear la cuenta. El backend lo genera al arrancar
 * si no hay cuenta y lo escribe en sus logs: quien abra la web antes que el
 * dueño no puede quedarse la cuenta sin acceso al servidor.
 *
 * Solo se guarda su hash. Tiene 80 bits de azar, así que basta con SHA-256.
 */
final class SetupCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function __construct(public readonly string $hash)
    {
    }

    /** @return array{0: string, 1: self} el código en claro (para el log) y lo que se guarda */
    public static function generate(): array
    {
        $chars = '';
        for ($i = 0; $i < 16; $i++) {
            $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        $plain = implode('-', str_split($chars, 4));

        return [$plain, new self(self::hashOf($plain))];
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function matches(string $plain): bool
    {
        return hash_equals($this->hash, self::hashOf($plain));
    }

    /** Sin guiones, espacios ni mayúsculas: da igual cómo se copie del log. */
    private static function hashOf(string $plain): string
    {
        return hash('sha256', strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $plain)));
    }
}

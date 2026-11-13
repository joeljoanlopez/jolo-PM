<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Port\PasswordHasher;

/**
 * Códigos de recuperación de un solo uso, por si se pierde el móvil. Se guardan
 * con el mismo hash que la contraseña y solo se enseñan al generarlos.
 */
final class RecoveryCodes
{
    public const COUNT = 10;

    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** @param list<string> $hashes */
    public function __construct(public readonly array $hashes)
    {
    }

    /** @return array{0: list<string>, 1: self} los códigos en claro y sus hashes */
    public static function generate(PasswordHasher $hasher): array
    {
        $plain = [];
        while (count($plain) < self::COUNT) {
            $chars = '';
            for ($i = 0; $i < 10; $i++) {
                $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $plain[] = substr($chars, 0, 5) . '-' . substr($chars, 5);
            $plain = array_values(array_unique($plain));
        }

        return [$plain, new self(array_map(static fn (string $code) => $hasher->hash(self::normalize($code)), $plain))];
    }

    /** Lo que queda tras gastar `$code`, o null si no es ninguno de los códigos. */
    public function consume(string $code, PasswordHasher $hasher): ?self
    {
        $code = self::normalize($code);
        if (strlen($code) !== 10) {
            return null;
        }
        foreach ($this->hashes as $i => $hash) {
            if ($hasher->verify($code, $hash)) {
                $remaining = $this->hashes;
                unset($remaining[$i]);

                return new self(array_values($remaining));
            }
        }

        return null;
    }

    public function remaining(): int
    {
        return count($this->hashes);
    }

    private static function normalize(string $code): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }
}

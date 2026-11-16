<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

use App\Domain\Service\Auth\RandomCode;

/**
 * Códigos de recuperación de un solo uso. Solo se guardan sus hashes: los
 * códigos en claro existen únicamente al generarlos, para enseñarlos una vez.
 */
final class RecoveryCodes
{
    public const COUNT = 10;

    /** @param list<string> $hashes */
    public function __construct(public readonly array $hashes)
    {
    }

    /** @return array{self, list<string>} los hashes y los códigos en claro */
    public static function generate(): array
    {
        $plain = [];
        while (count($plain) < self::COUNT) {
            $plain[RandomCode::generate(2, 5)] = true;
        }
        $plain = array_keys($plain);

        return [new self(array_map(RandomCode::hash(...), $plain)), $plain];
    }

    /** Los códigos que quedan sin `$code`, o null si `$code` no es ninguno de ellos. */
    public function consume(string $code): ?self
    {
        $hash = RandomCode::hash($code);
        foreach ($this->hashes as $i => $candidate) {
            if (hash_equals($candidate, $hash)) {
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
}

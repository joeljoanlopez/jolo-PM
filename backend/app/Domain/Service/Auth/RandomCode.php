<?php

declare(strict_types=1);

namespace App\Domain\Service\Auth;

/**
 * Códigos aleatorios para leer y teclear a mano (configuración inicial y
 * recuperación): mayúsculas y cifras sin las que se confunden (0/O, 1/I),
 * en grupos separados por guiones. Se comparan por hash SHA-256: son
 * aleatorios y largos, así que no necesitan un hash lento.
 */
final class RandomCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(int $groups, int $groupLength): string
    {
        $parts = [];
        for ($g = 0; $g < $groups; $g++) {
            $part = '';
            for ($i = 0; $i < $groupLength; $i++) {
                $part .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $parts[] = $part;
        }

        return implode('-', $parts);
    }

    /** Hash de lo tecleado, sin importar mayúsculas, espacios ni guiones. */
    public static function hash(string $code): string
    {
        return hash('sha256', strtoupper((string) preg_replace('/[\s-]/', '', $code)));
    }
}

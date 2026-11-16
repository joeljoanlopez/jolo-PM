<?php

declare(strict_types=1);

namespace App\Domain\Service\Auth;

/** Base32 de la RFC 4648 sin relleno: el formato de los secretos TOTP. */
final class Base32
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        if ($bytes === '') {
            return '';
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    /** Admite minúsculas, espacios y relleno `=`. */
    public static function decode(string $text): string
    {
        $text = strtoupper((string) preg_replace('/[\s=]/', '', $text));
        if (strspn($text, self::ALPHABET) !== strlen($text)) {
            throw new \InvalidArgumentException('Base32 no válido.');
        }

        $bits = '';
        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte) & 0xFF);
            }
        }

        return $out;
    }
}

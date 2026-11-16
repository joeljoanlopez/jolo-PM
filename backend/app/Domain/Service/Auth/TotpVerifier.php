<?php

declare(strict_types=1);

namespace App\Domain\Service\Auth;

/**
 * Códigos TOTP de la RFC 6238 (HOTP de la RFC 4226 sobre el tiempo), con
 * `hash_hmac` y sin librerías: 6 dígitos, periodos de 30 s y SHA-1, lo que
 * entiende cualquier app de autenticación.
 *
 * Acepta el periodo anterior y el siguiente para tolerar relojes desajustados,
 * pero nunca uno igual o anterior al último usado: así cada código vale una
 * sola vez aunque siga dentro de su ventana.
 */
final class TotpVerifier
{
    public const PERIOD = 30;
    public const DIGITS = 6;
    public const WINDOW = 1;

    /** Secreto nuevo de 160 bits (el tamaño que recomienda la RFC 4226), en Base32. */
    public static function generateSecret(): string
    {
        return Base32::encode(random_bytes(20));
    }

    /** URI `otpauth://` que las apps leen del QR. */
    public static function uri(string $secret, string $issuer, string $account): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?%s',
            rawurlencode($issuer),
            rawurlencode($account),
            http_build_query([
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => 'SHA1',
                'digits' => self::DIGITS,
                'period' => self::PERIOD,
            ], '', '&', PHP_QUERY_RFC3986),
        );
    }

    public static function periodAt(\DateTimeImmutable $time): int
    {
        return intdiv($time->getTimestamp(), self::PERIOD);
    }

    /**
     * Código de un periodo. `$key` son los bytes del secreto (no el Base32);
     * dígitos y algoritmo solo cambian para los vectores de la RFC.
     */
    public static function codeAt(
        string $key,
        int $period,
        int $digits = self::DIGITS,
        string $algorithm = 'sha1',
    ): string {
        $hash = hash_hmac($algorithm, pack('J', $period), $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** $digits), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Periodo con el que casa el código, o null si no vale (mal formado, fuera
     * de la ventana o ya usado).
     */
    public static function verify(
        string $secret,
        string $code,
        \DateTimeImmutable $now,
        ?int $lastUsedPeriod = null,
    ): ?int {
        $code = (string) preg_replace('/\s/', '', $code);
        if (preg_match('/^\d{' . self::DIGITS . '}$/', $code) !== 1) {
            return null;
        }

        $key = Base32::decode($secret);
        $current = self::periodAt($now);
        for ($period = $current - self::WINDOW; $period <= $current + self::WINDOW; $period++) {
            if ($lastUsedPeriod !== null && $period <= $lastUsedPeriod) {
                continue;
            }
            if (hash_equals(self::codeAt($key, $period), $code)) {
                return $period;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/**
 * Códigos de un solo uso basados en tiempo (RFC 6238, HMAC-SHA1), los de
 * cualquier app de autenticación: 6 dígitos que cambian cada 30 segundos.
 *
 * Acepta el periodo anterior y el siguiente para tolerar relojes desajustados,
 * pero nunca uno igual o anterior al último usado: así un código vale una vez.
 */
final class TotpVerifier
{
    public function __construct(
        private readonly int $digits = 6,
        private readonly int $period = 30,
        private readonly int $window = 1,
        private readonly string $algorithm = 'sha1',
    ) {
    }

    /** Secreto nuevo de 160 bits en Base32, el tamaño que recomienda RFC 4226. */
    public function generateSecret(): string
    {
        return Base32::encode(random_bytes(20));
    }

    public function periodAt(int $timestamp): int
    {
        return intdiv($timestamp, $this->period);
    }

    /** El código de un periodo. `$secret` en Base32. */
    public function code(string $secret, int $period): string
    {
        return $this->codeForKey(Base32::decode($secret), $period);
    }

    /**
     * Comprueba un código. Devuelve el periodo con el que casa, para guardarlo
     * como último usado, o null si no vale.
     */
    public function verify(string $secret, string $code, int $timestamp, ?int $lastUsedPeriod): ?int
    {
        $code = (string) preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{' . $this->digits . '}$/', $code)) {
            return null;
        }

        $key = Base32::decode($secret);
        $current = $this->periodAt($timestamp);
        for ($period = $current - $this->window; $period <= $current + $this->window; $period++) {
            if ($lastUsedPeriod !== null && $period <= $lastUsedPeriod) {
                continue;
            }
            if (hash_equals($this->codeForKey($key, $period), $code)) {
                return $period;
            }
        }

        return null;
    }

    /** URI que leen las apps de autenticación (también desde un QR). */
    public function provisioningUri(string $secret, string $issuer, string $account): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?%s',
            rawurlencode($issuer),
            rawurlencode($account),
            http_build_query([
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => strtoupper($this->algorithm),
                'digits' => $this->digits,
                'period' => $this->period,
            ], '', '&', PHP_QUERY_RFC3986),
        );
    }

    /** HOTP (RFC 4226) con el periodo como contador. `$key` en binario. */
    public function codeForKey(string $key, int $period): string
    {
        $counter = pack('J', $period);
        $hash = hash_hmac($this->algorithm, $counter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }
}

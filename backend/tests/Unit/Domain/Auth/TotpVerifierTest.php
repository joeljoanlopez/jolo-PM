<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Auth;

use App\Domain\Service\Auth\Base32;
use App\Domain\Service\Auth\TotpVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpVerifierTest extends TestCase
{
    /**
     * Vectores del apéndice B de la RFC 6238 (8 dígitos, periodos de 30 s).
     *
     * @return iterable<string, array{string, string, int, string}>
     */
    public static function rfc6238(): iterable
    {
        $seeds = [
            'sha1' => '12345678901234567890',
            'sha256' => '12345678901234567890123456789012',
            'sha512' => '1234567890123456789012345678901234567890123456789012345678901234',
        ];
        $vectors = [
            59 => ['94287082', '46119246', '90693936'],
            1111111109 => ['07081804', '68084774', '25091201'],
            1111111111 => ['14050471', '67062674', '99943326'],
            1234567890 => ['89005924', '91819424', '93441116'],
            2000000000 => ['69279037', '90698825', '38618901'],
            20000000000 => ['65353130', '77737706', '47863826'],
        ];
        foreach ($vectors as $time => $codes) {
            foreach (array_keys($seeds) as $i => $algorithm) {
                yield "$algorithm @ $time" => [$algorithm, $seeds[$algorithm], $time, $codes[$i]];
            }
        }
    }

    #[DataProvider('rfc6238')]
    public function test_matches_the_rfc_6238_vectors(string $algorithm, string $seed, int $time, string $code): void
    {
        $this->assertSame($code, TotpVerifier::codeAt($seed, intdiv($time, 30), 8, $algorithm));
    }

    public function test_accepts_the_current_code_and_one_period_either_side(): void
    {
        $secret = Base32::encode('12345678901234567890');
        $now = new \DateTimeImmutable('@1111111111');
        $period = TotpVerifier::periodAt($now);

        foreach ([-1, 0, 1] as $offset) {
            $code = TotpVerifier::codeAt('12345678901234567890', $period + $offset);
            $this->assertSame($period + $offset, TotpVerifier::verify($secret, $code, $now));
        }
        foreach ([-2, 2] as $offset) {
            $code = TotpVerifier::codeAt('12345678901234567890', $period + $offset);
            $this->assertNull(TotpVerifier::verify($secret, $code, $now));
        }
    }

    public function test_a_code_is_rejected_once_its_period_or_a_later_one_has_been_used(): void
    {
        $secret = Base32::encode('12345678901234567890');
        $now = new \DateTimeImmutable('@1111111111');
        $period = TotpVerifier::periodAt($now);
        $code = TotpVerifier::codeAt('12345678901234567890', $period);

        $this->assertNull(TotpVerifier::verify($secret, $code, $now, $period));
        $this->assertNull(TotpVerifier::verify($secret, $code, $now, $period + 1));
        $this->assertSame($period, TotpVerifier::verify($secret, $code, $now, $period - 1));
    }

    public function test_ignores_spaces_and_rejects_malformed_codes(): void
    {
        $secret = Base32::encode('12345678901234567890');
        $now = new \DateTimeImmutable('@59');
        $code = TotpVerifier::codeAt('12345678901234567890', 1);

        $this->assertSame(1, TotpVerifier::verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3), $now));
        $this->assertNull(TotpVerifier::verify($secret, 'abcdef', $now));
        $this->assertNull(TotpVerifier::verify($secret, '12345', $now));
        $this->assertNull(TotpVerifier::verify($secret, '', $now));
    }

    public function test_new_secrets_are_160_bit_base32_and_different_each_time(): void
    {
        $secret = TotpVerifier::generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(20, strlen(Base32::decode($secret)));
        $this->assertNotSame($secret, TotpVerifier::generateSecret());
    }

    public function test_builds_the_otpauth_uri_for_the_qr(): void
    {
        $this->assertSame(
            'otpauth://totp/jolo-PM:jolo-PM?secret=ABC&issuer=jolo-PM&algorithm=SHA1&digits=6&period=30',
            TotpVerifier::uri('ABC', 'jolo-PM', 'jolo-PM'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Auth\Base32;
use App\Domain\Auth\TotpVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpVerifierTest extends TestCase
{
    /** Apéndice B de RFC 6238, HMAC-SHA1 con 8 dígitos. */
    public static function rfcVectors(): iterable
    {
        yield [59, '94287082'];
        yield [1111111109, '07081804'];
        yield [1111111111, '14050471'];
        yield [1234567890, '89005924'];
        yield [2000000000, '69279037'];
        yield [20000000000, '65353130'];
    }

    #[DataProvider('rfcVectors')]
    public function test_rfc_6238_vectors(int $time, string $expected): void
    {
        $totp = new TotpVerifier(digits: 8);
        $secret = Base32::encode('12345678901234567890');

        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret);
        $this->assertSame($expected, $totp->code($secret, $totp->periodAt($time)));
        $this->assertSame($totp->periodAt($time), $totp->verify($secret, $expected, $time, null));
    }

    public function test_accepts_previous_and_next_period_only(): void
    {
        $totp = new TotpVerifier();
        $secret = $totp->generateSecret();
        $now = 1_800_000_000;
        $period = $totp->periodAt($now);

        $this->assertSame($period - 1, $totp->verify($secret, $totp->code($secret, $period - 1), $now, null));
        $this->assertSame($period + 1, $totp->verify($secret, $totp->code($secret, $period + 1), $now, null));
        $this->assertNull($totp->verify($secret, $totp->code($secret, $period - 2), $now, null));
        $this->assertNull($totp->verify($secret, $totp->code($secret, $period + 2), $now, null));
    }

    public function test_a_code_is_valid_only_once(): void
    {
        $totp = new TotpVerifier();
        $secret = $totp->generateSecret();
        $now = 1_800_000_000;
        $code = $totp->code($secret, $totp->periodAt($now));

        $used = $totp->verify($secret, $code, $now, null);

        $this->assertNotNull($used);
        $this->assertNull($totp->verify($secret, $code, $now, $used), 'mismo código, mismo periodo');
        $this->assertNull($totp->verify($secret, $totp->code($secret, $used - 1), $now, $used), 'periodo anterior al usado');
        $this->assertSame($used + 1, $totp->verify($secret, $totp->code($secret, $used + 1), $now, $used));
    }

    public function test_rejects_malformed_codes(): void
    {
        $totp = new TotpVerifier();
        $secret = $totp->generateSecret();

        $this->assertNull($totp->verify($secret, '', 1_800_000_000, null));
        $this->assertNull($totp->verify($secret, '12345', 1_800_000_000, null));
        $this->assertNull($totp->verify($secret, 'abcdef', 1_800_000_000, null));
    }

    public function test_generated_secret_is_160_bits_of_base32(): void
    {
        $secret = (new TotpVerifier())->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(20, strlen(Base32::decode($secret)));
    }

    public function test_provisioning_uri(): void
    {
        $uri = (new TotpVerifier())->provisioningUri('ABCDEF', 'jolo-PM', 'jolo-PM');

        $this->assertSame('otpauth://totp/jolo-PM:jolo-PM?secret=ABCDEF&issuer=jolo-PM&algorithm=SHA1&digits=6&period=30', $uri);
    }
}

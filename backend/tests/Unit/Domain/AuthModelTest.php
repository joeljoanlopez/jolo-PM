<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Auth\PasswordPolicy;
use App\Domain\Auth\RecoveryCodes;
use App\Domain\Auth\SetupCode;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Port\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class AuthModelTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new class implements PasswordHasher {
            public function hash(string $plain): string
            {
                return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 4]);
            }

            public function verify(string $plain, string $hash): bool
            {
                return password_verify($plain, $hash);
            }
        };
    }

    public function test_recovery_codes_are_hashed_and_single_use(): void
    {
        [$plain, $codes] = RecoveryCodes::generate($this->hasher);

        $this->assertCount(10, $plain);
        $this->assertCount(10, array_unique($plain));
        foreach ($codes->hashes as $hash) {
            $this->assertNotContains($hash, $plain);
            $this->assertStringNotContainsString(str_replace('-', '', $plain[0]), $hash);
        }

        $remaining = $codes->consume($plain[3], $this->hasher);
        $this->assertNotNull($remaining);
        $this->assertSame(9, $remaining->remaining());
        $this->assertNull($remaining->consume($plain[3], $this->hasher), 'ya gastado');
        // Da igual cómo se escriba: mayúsculas, sin guion, con espacios.
        $this->assertNotNull($remaining->consume(' ' . strtoupper(str_replace('-', '', $plain[4])) . ' ', $this->hasher));
        $this->assertNull($codes->consume('nope0-nope0', $this->hasher));
    }

    public function test_setup_code_is_stored_as_hash_and_tolerates_formatting(): void
    {
        [$plain, $code] = SetupCode::generate();

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}(-[A-Z2-9]{4}){3}$/', $plain);
        $this->assertStringNotContainsString(str_replace('-', '', $plain), $code->hash);
        $this->assertTrue(SetupCode::fromHash($code->hash)->matches(strtolower(str_replace('-', ' ', $plain))));
        $this->assertFalse($code->matches('AAAA-AAAA-AAAA-AAAA'));
    }

    public function test_password_policy(): void
    {
        PasswordPolicy::validate('twelve chars', 'twelve chars');

        try {
            PasswordPolicy::validate('short', 'short');
            $this->fail('debería fallar');
        } catch (ValidationFailed $e) {
            $this->assertArrayHasKey('password', $e->errors);
        }

        $this->expectException(ValidationFailed::class);
        PasswordPolicy::validate('long enough password', 'long enough passw0rd');
    }
}

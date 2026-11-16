<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Auth;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\Password;
use App\Domain\Model\Auth\RecoveryCodes;
use App\Domain\Model\Auth\SetupCode;
use App\Domain\Service\Auth\Base32;
use App\Domain\Service\Auth\TotpVerifier;
use PHPUnit\Framework\TestCase;

/** Códigos de recuperación, de configuración, contraseña y la cuenta. */
final class CodesTest extends TestCase
{
    public function test_generates_ten_distinct_recovery_codes_and_keeps_only_their_hashes(): void
    {
        [$codes, $plain] = RecoveryCodes::generate();

        $this->assertCount(10, array_unique($plain));
        $this->assertSame(10, $codes->remaining());
        foreach ($plain as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z2-9]{5}-[A-Z2-9]{5}$/', $code);
            $this->assertNotContains($code, $codes->hashes);
        }
    }

    public function test_a_recovery_code_works_once_however_it_is_typed(): void
    {
        [$codes, $plain] = RecoveryCodes::generate();
        $typed = strtolower(str_replace('-', ' ', $plain[3]));

        $after = $codes->consume($typed);

        $this->assertNotNull($after);
        $this->assertSame(9, $after->remaining());
        $this->assertNull($after->consume($plain[3]));
        $this->assertNull($codes->consume('NOPE0-NOPE0'));
    }

    public function test_setup_codes_match_only_themselves(): void
    {
        [$code, $plain] = SetupCode::issue();

        $this->assertMatchesRegularExpression('/^([A-Z2-9]{4}-){3}[A-Z2-9]{4}$/', $plain);
        $this->assertNotSame($plain, $code->hash);
        $this->assertTrue($code->matches(strtolower($plain)));
        $this->assertFalse($code->matches('AAAA-AAAA-AAAA-AAAA'));
    }

    public function test_password_needs_twelve_characters_and_the_same_confirmation(): void
    {
        Password::validate('doce-letras!', 'doce-letras!');

        $cases = [['corta', 'corta', 'password'], ['doce-letras!', 'otra-cosa!!!', 'passwordConfirmation']];
        foreach ($cases as [$p, $c, $field]) {
            try {
                Password::validate($p, $c);
                $this->fail('Debería rechazarse');
            } catch (ValidationFailed $e) {
                $this->assertSame([$field], array_keys($e->errors));
            }
        }
    }

    public function test_the_account_remembers_the_last_totp_period_and_spends_recovery_codes(): void
    {
        [$codes, $plain] = RecoveryCodes::generate();
        $account = new Account(Account::newId(), 'hash', 'cifrado', null, $codes);
        $secret = Base32::encode('12345678901234567890');
        $now = new \DateTimeImmutable('@1111111111');
        $code = TotpVerifier::codeAt('12345678901234567890', TotpVerifier::periodAt($now));

        $used = $account->acceptTotp($secret, $code, $now);
        $this->assertNotNull($used);
        $this->assertSame(TotpVerifier::periodAt($now), $used->lastTotpPeriod);
        $this->assertNull($used->acceptTotp($secret, $code, $now));

        $spent = $account->consumeRecoveryCode($plain[0]);
        $this->assertSame(9, $spent?->recoveryCodes->remaining());
        $this->assertNull($spent->consumeRecoveryCode($plain[0]));
        $this->assertNotSame(Account::newId(), Account::newId());
    }
}

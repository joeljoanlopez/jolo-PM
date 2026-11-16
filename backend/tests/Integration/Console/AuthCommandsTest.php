<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\LoginAttempt;
use App\Domain\Model\Auth\LoginStep;
use App\Domain\Model\Auth\RecoveryCodes;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\LoginLog;
use Illuminate\Support\Facades\Date;
use Tests\Integration\IntegrationTestCase;

final class AuthCommandsTest extends IntegrationTestCase
{
    public function test_the_setup_code_is_printed_only_while_there_is_no_account(): void
    {
        $this->artisan('auth:setup-code')
            ->expectsOutputToContain('código de configuración')
            ->assertSuccessful();
        $this->assertDatabaseCount('setup_codes', 1);

        $this->app->make(AccountRepository::class)
            ->save(new Account('a', 'hash', 'secret', null, new RecoveryCodes([])));

        $this->artisan('auth:setup-code')
            ->doesntExpectOutputToContain('código de configuración')
            ->assertSuccessful();
    }

    public function test_reset_asks_for_confirmation(): void
    {
        $this->app->make(AccountRepository::class)
            ->save(new Account('a', 'hash', 'secret', null, new RecoveryCodes([])));

        $this->artisan('auth:reset')
            ->expectsConfirmation('Se borra la cuenta y se cierran todas las sesiones. ¿Seguir?', 'no')
            ->assertFailed();
        $this->assertDatabaseCount('accounts', 1);
    }

    public function test_login_records_older_than_the_configured_days_are_purged(): void
    {
        $log = $this->app->make(LoginLog::class);
        $daysAgo = fn (int $days) => Date::now()->subDays($days)->toDateTimeImmutable();
        $log->record(new LoginAttempt('1.1.1.1', false, LoginStep::Password, $daysAgo(91)));
        $log->record(new LoginAttempt('2.2.2.2', true, LoginStep::Totp, $daysAgo(89)));

        $this->artisan('auth:prune-logins')
            ->expectsOutput('1 registros de inicio de sesión purgados.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('login_attempts', ['ip' => '1.1.1.1']);
        $this->assertDatabaseHas('login_attempts', ['ip' => '2.2.2.2']);
    }
}

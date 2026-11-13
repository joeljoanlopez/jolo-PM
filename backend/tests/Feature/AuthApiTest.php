<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\IssueSetupCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // --- Protección -------------------------------------------------------

    public function test_without_session_no_data_route_answers(): void
    {
        $this->getJson('/api/health')->assertOk();

        foreach (['/api/projects', '/api/repositories', '/api/projects/1/board'] as $uri) {
            $this->getJson($uri)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated')
                ->assertJsonStructure(['error' => ['code', 'message']]);
        }
        $this->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['*.md']])->assertUnauthorized();
    }

    public function test_password_alone_does_not_give_access(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'login');

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertOk()->assertJsonPath('state', 'two_factor');

        $this->getJson('/api/auth/me')->assertJsonPath('state', 'two_factor');
        $this->getJson('/api/projects')->assertUnauthorized();

        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])
            ->assertOk()
            ->assertJsonPath('state', 'authenticated')
            ->assertJsonPath('recoveryCodesLeft', 10);
        $this->getJson('/api/projects')->assertOk();
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'authenticated');
    }

    public function test_wrong_password(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me');

        $this->postJson('/api/auth/login', ['password' => 'nope'])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_credentials');
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'login');
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])->assertUnauthorized();
    }

    public function test_second_factor_must_arrive_within_five_minutes(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me');
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();

        $this->clock->advance(301);

        $this->getJson('/api/auth/me')->assertJsonPath('state', 'login');
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_used_totp_code_is_rejected_even_inside_its_window(): void
    {
        $this->createAccount();
        $code = $this->totpCode();
        $this->login();
        $this->postJson('/api/auth/two-factor', ['code' => $code])->assertOk();
        $this->postJson('/api/auth/logout')->assertNoContent();

        $this->login();
        $this->postJson('/api/auth/two-factor', ['code' => $code])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_credentials');
        // El del periodo siguiente (dentro de la ventana de ±30 s) sí vale.
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode(1)])->assertOk();
    }

    public function test_recovery_code_works_once(): void
    {
        $codes = $this->createAccount();

        $this->login();
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $codes[0]])
            ->assertOk()->assertJsonPath('recoveryCodesLeft', 9);
        $this->postJson('/api/auth/logout')->assertNoContent();

        $this->login();
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $codes[0]])->assertStatus(422);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $codes[1]])->assertOk();
    }

    public function test_regenerate_recovery_codes(): void
    {
        $old = $this->createAccount();
        $this->login();
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])->assertOk();

        $new = $this->postJson('/api/auth/recovery-codes')->assertOk()->json('recoveryCodes');
        $this->assertCount(10, $new);
        $this->postJson('/api/auth/logout');

        $this->login();
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $old[0]])->assertStatus(422);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $new[0]])->assertOk();
    }

    public function test_regenerating_recovery_codes_needs_a_session(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me');

        $this->postJson('/api/auth/recovery-codes')->assertUnauthorized();
    }

    public function test_logout_closes_the_session(): void
    {
        $this->signIn();
        $this->getJson('/api/projects')->assertOk();

        $this->postJson('/api/auth/logout')->assertNoContent();

        $this->getJson('/api/projects')->assertUnauthorized();
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'login');
    }

    // --- Configuración inicial -------------------------------------------

    public function test_initial_setup_creates_the_account_and_signs_in(): void
    {
        $setupCode = $this->app->make(IssueSetupCode::class)->execute();
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'setup');
        $this->getJson('/api/auth/setup')->assertOk()->assertJsonPath('required', true);
        $this->postJson('/api/auth/login', ['password' => 'x'])->assertStatus(409)->assertJsonPath('error.code', 'setup_required');

        $this->postJson('/api/auth/setup/code', ['setupCode' => $setupCode])->assertNoContent();

        $this->postJson('/api/auth/setup/password', ['setupCode' => $setupCode, 'password' => 'short', 'passwordConfirmation' => 'short'])
            ->assertStatus(422)->assertJsonPath('error.fields.password', fn ($m) => str_contains($m, '12'));
        $this->postJson('/api/auth/setup/password', ['setupCode' => $setupCode, 'password' => self::PASSWORD, 'passwordConfirmation' => 'otra cosa distinta'])
            ->assertStatus(422)->assertJsonPath('error.fields.passwordConfirmation', fn ($m) => $m !== '');

        $enrollment = $this->postJson('/api/auth/setup/password', [
            'setupCode' => $setupCode,
            'password' => self::PASSWORD,
            'passwordConfirmation' => self::PASSWORD,
        ])->assertOk()->json();
        $this->assertStringStartsWith('otpauth://totp/jolo-PM:jolo-PM?secret=' . $enrollment['secret'], $enrollment['otpauthUri']);

        // Hasta confirmar un código válido no hay cuenta.
        $this->postJson('/api/auth/setup/two-factor', ['setupCode' => $setupCode, 'code' => '000000'])->assertStatus(422);
        $this->assertSame(0, DB::table('accounts')->count());

        $codes = $this->postJson('/api/auth/setup/two-factor', [
            'setupCode' => $setupCode,
            'code' => $this->totpCode(0, $enrollment['secret']),
        ])->assertOk()->json('recoveryCodes');

        $this->assertCount(10, $codes);
        $this->assertSame(1, DB::table('accounts')->count());
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'authenticated');
        $this->getJson('/api/projects')->assertOk();

        // El asistente y su código dejan de existir.
        $this->getJson('/api/auth/setup')->assertNotFound();
        $this->postJson('/api/auth/setup/code', ['setupCode' => $setupCode])->assertNotFound();
        $this->assertSame(0, DB::table('setup_codes')->count());

        // Y se entra con la contraseña elegida.
        $this->postJson('/api/auth/logout');
        $this->getJson('/api/auth/me');
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode(1, $enrollment['secret'])])->assertOk();
    }

    public function test_setup_needs_the_code_from_the_logs(): void
    {
        $this->app->make(IssueSetupCode::class)->execute();
        $this->getJson('/api/auth/me');

        $this->postJson('/api/auth/setup/code', ['setupCode' => 'AAAA-BBBB-CCCC-DDDD'])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_credentials');
        $this->postJson('/api/auth/setup/password', ['setupCode' => 'AAAA-BBBB-CCCC-DDDD', 'password' => self::PASSWORD, 'passwordConfirmation' => self::PASSWORD])
            ->assertStatus(422);
        $this->postJson('/api/auth/setup/two-factor', ['setupCode' => 'AAAA-BBBB-CCCC-DDDD', 'code' => '123456'])
            ->assertUnauthorized();

        $this->assertSame(0, DB::table('accounts')->count());
    }

    public function test_a_new_boot_replaces_the_setup_code(): void
    {
        $first = $this->app->make(IssueSetupCode::class)->execute();
        $second = $this->app->make(IssueSetupCode::class)->execute();
        $this->getJson('/api/auth/me');

        $this->postJson('/api/auth/setup/code', ['setupCode' => $first])->assertStatus(422);
        $this->postJson('/api/auth/setup/code', ['setupCode' => $second])->assertNoContent();
    }

    public function test_setup_code_command_prints_the_code_only_without_account(): void
    {
        $this->artisan('auth:setup-code')->expectsOutputToContain('código')->assertSuccessful();
        $this->assertSame(1, DB::table('setup_codes')->count());

        $this->createAccount();
        $this->artisan('auth:setup-code')->doesntExpectOutputToContain('código')->assertSuccessful();
        $this->assertSame(0, DB::table('setup_codes')->count());
    }

    // --- Límite de intentos ----------------------------------------------

    public function test_sixth_failed_login_in_a_minute_is_429(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['password' => 'nope'])->assertStatus(422);
        }
        $response = $this->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_attempts');
        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));

        // Por IP: otra IP puede seguir.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
    }

    public function test_sixth_failed_second_factor_is_429(): void
    {
        $this->createAccount();
        $this->login();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/two-factor', ['code' => '000000'])->assertStatus(422);
        }
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])->assertStatus(429);
    }

    public function test_sixth_failed_setup_code_is_429(): void
    {
        $code = $this->app->make(IssueSetupCode::class)->execute();
        $this->getJson('/api/auth/me');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/setup/code', ['setupCode' => 'nope'])->assertStatus(422);
        }
        $this->postJson('/api/auth/setup/code', ['setupCode' => $code])->assertStatus(429);
    }

    // --- Sesión, CSRF y almacenamiento -----------------------------------

    public function test_session_cookie_is_http_only_secure_and_strict(): void
    {
        $response = $this->getJson('/api/auth/me');

        $cookies = collect($response->headers->getCookies())->keyBy(fn ($c) => $c->getName());
        $session = $cookies[config('session.cookie')];
        $this->assertTrue($session->isHttpOnly());
        $this->assertTrue($session->isSecure());
        $this->assertSame('strict', $session->getSameSite());
        $this->assertSame('strict', $cookies['XSRF-TOKEN']->getSameSite());
        // La web lee XSRF-TOKEN para mandarla en la cabecera.
        $this->assertFalse($cookies['XSRF-TOKEN']->isHttpOnly());
    }

    public function test_modifying_requests_need_the_csrf_header(): void
    {
        $this->signIn();
        $this->sendXsrfHeader = false;

        $this->postJson('/api/auth/logout')->assertStatus(419)->assertJsonPath('error.code', 'csrf_mismatch');
        $this->postJson('/api/auth/logout', [], ['X-XSRF-TOKEN' => 'basura'])->assertStatus(419);
        $this->getJson('/api/projects')->assertOk();

        $this->sendXsrfHeader = true;
        $this->postJson('/api/auth/logout')->assertNoContent();
    }

    public function test_login_without_csrf_cookie_fails(): void
    {
        $this->createAccount();

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertStatus(419);
    }

    public function test_secrets_are_not_stored_in_clear(): void
    {
        $codes = $this->createAccount();

        $dump = json_encode(DB::table('accounts')->get());
        $this->assertStringNotContainsString(self::TOTP_SECRET, $dump);
        $this->assertStringNotContainsString(self::PASSWORD, $dump);
        foreach ($codes as $code) {
            $this->assertStringNotContainsString($code, $dump);
            $this->assertStringNotContainsString(str_replace('-', '', $code), $dump);
        }
    }

    public function test_every_login_attempt_is_logged_with_ip(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me');
        $this->postJson('/api/auth/login', ['password' => 'nope']);
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD]);
        $this->postJson('/api/auth/two-factor', ['code' => '000000']);
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()]);

        $log = DB::table('login_attempts')->orderBy('id')->get(['ip', 'method', 'success', 'created_at']);
        $this->assertSame(
            [['password', 0], ['password', 1], ['totp', 0], ['totp', 1]],
            $log->map(fn ($row) => [$row->method, (int) $row->success])->all(),
        );
        $this->assertSame('127.0.0.1', $log[0]->ip);
        $this->assertNotNull($log[0]->created_at);
    }

    public function test_reset_command_returns_to_setup_and_closes_sessions(): void
    {
        $this->signIn();
        DB::table('projects')->insert(['owner' => 'o', 'name' => 'r', 'paths' => '["*.md"]']);

        $this->artisan('auth:reset', ['--force' => true])->expectsOutputToContain('código')->assertSuccessful();

        $this->getJson('/api/projects')->assertUnauthorized();
        $this->getJson('/api/auth/me')->assertJsonPath('state', 'setup');
        $this->assertSame(1, DB::table('projects')->count());
        $this->assertSame(1, DB::table('setup_codes')->count());
    }

    private function login(): void
    {
        $this->getJson('/api/auth/me');
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
    }
}

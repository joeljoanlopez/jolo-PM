<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Auth\IssueSetupCode;
use App\Domain\Service\Auth\Base32;
use App\Domain\Service\Auth\TotpVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Tests\Integration\IntegrationTestCase;
use Tests\Support\FakeGitHub;

/**
 * Login con 2FA (E10) de extremo a extremo por HTTP: configuración inicial,
 * login en dos pasos, códigos de recuperación, límite de intentos, CSRF y 401
 * sin sesión. Con SQLite en fichero y la hora fijada.
 */
final class AuthFlowTest extends IntegrationTestCase
{
    use FakeGitHub;

    private const PASSWORD = 'una contraseña larga';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGitHub();
        $this->travelTo(Date::parse('2026-11-16 12:00:00'));
        // Como el navegador desde la propia web; el CSRF se prueba aparte.
        $this->withHeaders(['Accept-Language' => 'es', 'Sec-Fetch-Site' => 'same-origin']);
    }

    public function test_initial_setup_creates_the_account_and_leaves_the_session_open(): void
    {
        $this->getJson('/api/auth/setup')->assertOk()->assertExactJson(['required' => true]);
        $this->getJson('/api/auth/me')->assertOk()->assertExactJson(['state' => 'setup']);
        $code = $this->issueSetupCode();

        $enrollment = $this->postJson('/api/auth/setup/code', ['setupCode' => strtolower($code)])
            ->assertOk()
            ->assertJsonStructure(['secret', 'uri']);
        $secret = $enrollment->json('secret');
        $this->assertStringStartsWith('otpauth://totp/jolo-PM:jolo-PM?secret=' . $secret, $enrollment->json('uri'));

        $this->postJson('/api/auth/setup/password', ['password' => 'corta', 'passwordConfirmation' => 'corta'])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.password', 'La contraseña debe tener al menos 12 caracteres.');
        $this->postJson('/api/auth/setup/password', $this->passwords())->assertNoContent();
        $this->postJson('/api/auth/setup/complete', [...$this->passwords(), 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_credentials');

        $codes = $this->postJson('/api/auth/setup/complete', [...$this->passwords(), 'code' => $this->totp($secret)])
            ->assertCreated()
            ->json('recoveryCodes');

        $this->assertCount(10, $codes);
        $this->getJson('/api/auth/me')->assertExactJson(['state' => 'authenticated']);
        $this->getJson('/api/projects')->assertOk();

        // En la base de datos no queda en claro ni el secreto ni los códigos.
        $row = json_encode(DB::table('accounts')->first());
        $this->assertStringNotContainsString($secret, (string) $row);
        foreach ($codes as $recoveryCode) {
            $this->assertStringNotContainsString($recoveryCode, (string) $row);
        }
        $this->assertStringNotContainsString(self::PASSWORD, (string) $row);
    }

    public function test_without_the_setup_code_no_account_can_be_created_and_after_setup_the_wizard_is_gone(): void
    {
        $this->issueSetupCode();
        $this->postJson('/api/auth/setup/code', ['setupCode' => 'AAAA-BBBB-CCCC-DDDD'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'Código de configuración incorrecto. Está en los logs del backend.');
        $this->postJson('/api/auth/setup/complete', [...$this->passwords(), 'code' => '123456'])
            ->assertStatus(422);
        $this->assertDatabaseCount('accounts', 0);

        $this->createAccount();

        $this->getJson('/api/auth/setup')->assertOk()->assertExactJson(['required' => false]);
        $this->postJson('/api/auth/setup/code', ['setupCode' => 'AAAA-BBBB-CCCC-DDDD'])->assertNotFound();
        $this->postJson('/api/auth/setup/password', $this->passwords())->assertNotFound();
        $this->postJson('/api/auth/setup/complete', [...$this->passwords(), 'code' => '123456'])->assertNotFound();
        $this->assertDatabaseCount('setup_codes', 0);
    }

    public function test_without_a_session_no_data_route_answers(): void
    {
        $this->createAccount();

        $this->getJson('/api/projects')
            ->assertUnauthorized()
            ->assertExactJson(['error' => ['code' => 'unauthenticated', 'message' => 'Inicia sesión para continuar.']]);
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['*.md']])->assertUnauthorized();
        $this->getJson('/api/repositories')->assertUnauthorized();
        $this->getJson('/api/projects/1/board')->assertUnauthorized();
        $this->postJson('/api/auth/recovery-codes', ['code' => '123456'])->assertUnauthorized();
        $this->getJson('/api/health')->assertOk();
    }

    public function test_login_needs_the_password_and_then_the_second_factor(): void
    {
        $secret = $this->createAccount();

        $this->postJson('/api/auth/login', ['password' => 'mala'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'Contraseña incorrecta.');
        $this->postJson('/api/auth/two-factor', ['code' => $this->totp($secret)])->assertUnauthorized();

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertOk()
            ->assertExactJson(['state' => 'two_factor']);
        // Con la contraseña sola no hay acceso.
        $this->getJson('/api/projects')->assertUnauthorized();

        $this->travel(30)->seconds();
        $this->postJson('/api/auth/two-factor', ['code' => $this->totp($secret)])
            ->assertOk()
            ->assertJsonPath('state', 'authenticated');
        $this->getJson('/api/projects')->assertOk();

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->getJson('/api/projects')->assertUnauthorized();
        $this->getJson('/api/auth/me')->assertExactJson(['state' => 'login']);

        $this->assertDatabaseHas('login_attempts', ['succeeded' => false, 'step' => 'password', 'ip' => '127.0.0.1']);
        $this->assertDatabaseHas('login_attempts', ['succeeded' => true, 'step' => 'totp']);
    }

    public function test_the_pending_login_expires_after_five_minutes(): void
    {
        $secret = $this->createAccount();
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();

        $this->travel(6)->minutes();

        $this->postJson('/api/auth/two-factor', ['code' => $this->totp($secret)])
            ->assertUnauthorized()
            ->assertJsonPath('error.message', 'El inicio de sesión ha caducado; vuelve a meter la contraseña.');
        $this->getJson('/api/auth/me')->assertExactJson(['state' => 'login']);
    }

    public function test_a_used_totp_code_is_rejected_while_still_in_its_window(): void
    {
        $secret = $this->createAccount();
        $this->travel(30)->seconds();
        $code = $this->totp($secret);
        $this->signIn($code);
        $this->postJson('/api/auth/logout');

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
        $this->postJson('/api/auth/two-factor', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'Código incorrecto o ya usado.');
        $this->assertDatabaseHas('login_attempts', ['succeeded' => false, 'step' => 'totp']);
    }

    public function test_a_recovery_code_works_only_once(): void
    {
        $this->issueSetupCode();
        [$secret, $codes] = $this->completeSetup();
        $this->postJson('/api/auth/logout');

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD]);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $codes[0]])
            ->assertOk()
            ->assertJsonPath('recoveryCodesLeft', 9);
        $this->postJson('/api/auth/logout');

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD]);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $codes[0]])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'Código de recuperación incorrecto o ya usado.');
        $this->postJson('/api/auth/two-factor', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_regenerating_recovery_codes_needs_a_valid_totp_code_even_with_a_session(): void
    {
        $this->issueSetupCode();
        [$secret, $old] = $this->completeSetup();

        $this->postJson('/api/auth/recovery-codes', ['code' => '000000'])->assertStatus(422);
        $this->postJson('/api/auth/recovery-codes', [])->assertStatus(422);

        $this->travel(30)->seconds();
        $new = $this->postJson('/api/auth/recovery-codes', ['code' => $this->totp($secret)])
            ->assertOk()
            ->json('recoveryCodes');
        $this->assertCount(10, $new);
        $this->assertSame([], array_intersect($old, $new));

        $this->postJson('/api/auth/logout');
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD]);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $old[0]])->assertStatus(422);
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => $new[0]])->assertOk();
    }

    public function test_the_sixth_failed_attempt_in_a_minute_gets_429(): void
    {
        $this->createAccount();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(422);
        }
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertStatus(429)
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('error.code', 'too_many_attempts')
            ->assertJsonPath('error.message', 'Demasiados intentos. Vuelve a probar en 60 s.');

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
    }

    public function test_the_second_factor_and_the_setup_code_are_limited_too(): void
    {
        $this->issueSetupCode();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/setup/code', ['setupCode' => 'AAAA'])->assertStatus(422);
        }
        $this->postJson('/api/auth/setup/code', ['setupCode' => 'AAAA'])->assertStatus(429);

        $this->travel(61)->seconds();
        $this->createAccount();
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/two-factor', ['code' => '000000'])->assertStatus(422);
        }
        $this->postJson('/api/auth/two-factor', ['recoveryCode' => 'AAAAA-AAAAA'])->assertStatus(429);
    }

    public function test_the_limit_is_per_client_ip_behind_a_trusted_proxy_only(): void
    {
        $this->createAccount();
        $fromProxy = fn (string $client) => $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->withHeader('X-Forwarded-For', "1.2.3.4, $client");

        // Sin proxy de confianza, X-Forwarded-For no cuenta: todos son 172.18.0.5.
        for ($i = 0; $i < 5; $i++) {
            $fromProxy("10.0.0.$i")->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(422);
        }
        $fromProxy('10.0.0.9')->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(429);

        // Con el proxy de confianza, cada cliente tiene su límite. La IP es la
        // última que añade el proxy: la primera (1.2.3.4) la ha podido inventar
        // el cliente.
        config(['trustedproxy.proxies' => 'REMOTE_ADDR']);
        for ($i = 0; $i < 5; $i++) {
            $fromProxy('10.0.0.1')->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(422);
        }
        $fromProxy('10.0.0.1')->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(429);
        $fromProxy('10.0.0.2')->postJson('/api/auth/login', ['password' => 'mala'])->assertStatus(422);
        $this->assertDatabaseHas('login_attempts', ['ip' => '10.0.0.2']);
        $this->assertDatabaseMissing('login_attempts', ['ip' => '1.2.3.4']);
    }

    public function test_requests_that_change_something_need_the_csrf_token(): void
    {
        $this->createAccount();
        $this->flushHeaders();

        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertStatus(419)
            ->assertJsonPath('error.code', 'csrf_mismatch');
        $this->withHeader('Sec-Fetch-Site', 'cross-site')
            ->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertStatus(419);

        // La web lee la cookie XSRF-TOKEN y la devuelve en la cabecera X-XSRF-TOKEN.
        $this->flushHeaders();
        $cookie = $this->getJson('/api/auth/me')->assertOk()->getCookie('XSRF-TOKEN', false);
        $this->assertNotNull($cookie);
        $this->assertFalse($cookie->isHttpOnly());
        $this->withHeader('X-XSRF-TOKEN', (string) $cookie->getValue())
            ->postJson('/api/auth/login', ['password' => self::PASSWORD])
            ->assertOk();
    }

    public function test_the_session_cookie_is_http_only_secure_and_strict(): void
    {
        $cookie = $this->getJson('/api/auth/me')->getCookie((string) config('session.cookie'), false);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('strict', $cookie->getSameSite());
    }

    public function test_resetting_the_account_closes_every_session_and_keeps_the_projects(): void
    {
        $this->issueSetupCode();
        $this->completeSetup();
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->assertCreated();

        $this->artisan('auth:reset', ['--force' => true])
            ->expectsOutputToContain('Cuenta borrada')
            ->assertSuccessful();

        $this->getJson('/api/projects')->assertUnauthorized();
        $this->getJson('/api/auth/me')->assertExactJson(['state' => 'setup']);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('setup_codes', 1);
    }

    private function issueSetupCode(): string
    {
        return (string) $this->app->make(IssueSetupCode::class)->execute();
    }

    /**
     * El asistente entero; la sesión queda iniciada.
     *
     * @return array{string, list<string>} el secreto TOTP y los códigos de recuperación
     */
    private function completeSetup(): array
    {
        $code = $this->issueSetupCode();
        $secret = $this->postJson('/api/auth/setup/code', ['setupCode' => $code])->assertOk()->json('secret');
        $codes = $this->postJson('/api/auth/setup/complete', [...$this->passwords(), 'code' => $this->totp($secret)])
            ->assertCreated()
            ->json('recoveryCodes');

        return [$secret, $codes];
    }

    /** Cuenta creada y sesión cerrada. @return string el secreto TOTP */
    private function createAccount(): string
    {
        [$secret] = $this->completeSetup();
        $this->postJson('/api/auth/logout')->assertNoContent();

        return $secret;
    }

    private function signIn(string $code): TestResponse
    {
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();

        return $this->postJson('/api/auth/two-factor', ['code' => $code])->assertOk();
    }

    /** @return array{password: string, passwordConfirmation: string} */
    private function passwords(): array
    {
        return ['password' => self::PASSWORD, 'passwordConfirmation' => self::PASSWORD];
    }

    private function totp(string $secret): string
    {
        $now = new \DateTimeImmutable('@' . Date::now()->getTimestamp());

        return TotpVerifier::codeAt(Base32::decode($secret), TotpVerifier::periodAt($now));
    }
}

<?php

namespace Tests;

use App\Domain\Auth\Account;
use App\Domain\Auth\RecoveryCodes;
use App\Domain\Auth\TotpVerifier;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SecretCipher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FrozenClock;

abstract class TestCase extends BaseTestCase
{
    protected const PASSWORD = 'correct horse battery staple';
    protected const TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    /**
     * Cookies como las guarda un navegador: los valores cifrados tal cual
     * llegan. Cada petición las envía, y también la cabecera X-XSRF-TOKEN con
     * la cookie XSRF-TOKEN, como hace la web.
     *
     * @var array<string, string>
     */
    protected array $cookieJar = [];

    protected bool $sendXsrfHeader = true;

    protected FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FrozenClock();
        $this->app->instance(Clock::class, $this->clock);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $cookies = array_merge($this->cookieJar, $cookies);
        if ($this->sendXsrfHeader && isset($this->cookieJar['XSRF-TOKEN']) && !isset($server['HTTP_X_XSRF_TOKEN'])) {
            $server['HTTP_X_XSRF_TOKEN'] = $this->cookieJar['XSRF-TOKEN'];
        }

        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->isCleared()) {
                unset($this->cookieJar[$cookie->getName()]);
            } else {
                $this->cookieJar[$cookie->getName()] = (string) $cookie->getValue();
            }
        }

        return $response;
    }

    /**
     * Crea la cuenta sin pasar por el asistente.
     *
     * @return list<string> los códigos de recuperación en claro
     */
    protected function createAccount(): array
    {
        $hasher = $this->app->make(PasswordHasher::class);
        [$codes, $recovery] = RecoveryCodes::generate($hasher);
        $this->app->make(AccountRepository::class)->save(Account::create(
            $hasher->hash(self::PASSWORD),
            $this->app->make(SecretCipher::class)->encrypt(self::TOTP_SECRET),
            0,
            $recovery,
        ));

        return $codes;
    }

    /** El código TOTP del momento del reloj de los tests (más `$periods` periodos). */
    protected function totpCode(int $periods = 0, string $secret = self::TOTP_SECRET): string
    {
        $totp = new TotpVerifier();

        return $totp->code($secret, $totp->periodAt($this->clock->now()->getTimestamp()) + $periods);
    }

    /** Crea la cuenta e inicia sesión por la API, como lo haría la web. */
    protected function signIn(): void
    {
        $this->createAccount();
        $this->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/login', ['password' => self::PASSWORD])->assertOk();
        $this->postJson('/api/auth/two-factor', ['code' => $this->totpCode()])->assertOk();
        // El siguiente código TOTP tiene que ser de otro periodo.
        $this->clock->advance(30);
    }
}

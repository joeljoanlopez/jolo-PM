<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\RecoveryCodes;
use App\Domain\Port\AccountRepository;
use App\Infrastructure\Http\Auth\AuthSession;

/**
 * Para los tests que no son del login: una cuenta y una sesión ya
 * autenticada, y las peticiones como las de un navegador desde la propia web
 * (`Sec-Fetch-Site: same-origin`), que pasan el CSRF. El login de verdad se
 * prueba en Tests\Integration\Http\AuthFlowTest.
 */
trait SignsIn
{
    protected function signIn(): void
    {
        $account = new Account('test-account', 'hash', 'secret', null, new RecoveryCodes([]));
        $this->app->make(AccountRepository::class)->save($account);

        $this->withSession([AuthSession::AUTHENTICATED => $account->id])
            ->withHeader('Sec-Fetch-Site', 'same-origin');
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Model\Auth\SetupCode;
use App\Domain\Port\AccountRepository;

/**
 * Al arrancar sin cuenta: un código de configuración nuevo (el anterior deja
 * de valer). Con la cuenta creada no hace nada.
 */
final class IssueSetupCode
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    /** @return string|null el código en claro, o null si ya hay cuenta */
    public function execute(): ?string
    {
        if ($this->accounts->find() !== null) {
            return null;
        }

        [$code, $plain] = SetupCode::issue();
        $this->accounts->saveSetupCode($code);

        return $plain;
    }
}

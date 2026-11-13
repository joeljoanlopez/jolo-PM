<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\SetupCode;
use App\Domain\Port\AccountRepository;

/**
 * Al arrancar sin cuenta: genera un código de configuración nuevo (el anterior
 * deja de valer) y lo devuelve para escribirlo en los logs.
 */
final class IssueSetupCode
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    /** El código en claro, o null si ya hay cuenta y no hace falta. */
    public function execute(): ?string
    {
        if ($this->accounts->get() !== null) {
            $this->accounts->deleteSetupCode();

            return null;
        }

        [$plain, $code] = SetupCode::generate();
        $this->accounts->saveSetupCode($code);

        return $plain;
    }
}

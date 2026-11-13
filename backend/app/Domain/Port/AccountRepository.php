<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Auth\Account;
use App\Domain\Auth\SetupCode;

/** La cuenta única y, mientras no existe, el código para crearla. */
interface AccountRepository
{
    public function get(): ?Account;

    public function save(Account $account): void;

    /** Borra la cuenta (no los proyectos). */
    public function delete(): void;

    public function setupCode(): ?SetupCode;

    /** Sustituye el código anterior, si lo había. */
    public function saveSetupCode(SetupCode $code): void;

    public function deleteSetupCode(): void;
}

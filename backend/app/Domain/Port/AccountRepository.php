<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\SetupCode;

/** La cuenta única y, mientras no existe, el código de configuración inicial. */
interface AccountRepository
{
    public function find(): ?Account;

    /** Crea la cuenta o sustituye la que hay. */
    public function save(Account $account): void;

    /** Borra la cuenta y el código de configuración. Los proyectos no se tocan. */
    public function delete(): void;

    public function findSetupCode(): ?SetupCode;

    /** Sustituye el código anterior, si lo había. */
    public function saveSetupCode(SetupCode $code): void;

    public function deleteSetupCode(): void;
}

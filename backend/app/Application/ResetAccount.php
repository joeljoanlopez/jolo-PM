<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Port\AccountRepository;

/**
 * Para recuperar el acceso si se pierde todo: borra la cuenta (no los
 * proyectos) y emite un código de configuración nuevo. Las sesiones abiertas
 * dejan de valer porque eran de la cuenta borrada.
 */
final class ResetAccount
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly IssueSetupCode $issue,
    ) {
    }

    /** @return string el código de configuración nuevo */
    public function execute(): string
    {
        $this->accounts->delete();

        return (string) $this->issue->execute();
    }
}

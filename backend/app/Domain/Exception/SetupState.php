<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** La operación no corresponde al estado de la cuenta (ya creada, o aún no). */
final class SetupState extends DomainException
{
    public function __construct(Message $reason, public readonly bool $accountExists)
    {
        parent::__construct($reason);
    }

    /** El asistente ya no existe: la cuenta está creada. */
    public static function alreadyDone(): self
    {
        return new self(new Message('auth.setup_done'), true);
    }

    /** Aún no hay cuenta: toca el asistente, no el login. */
    public static function required(): self
    {
        return new self(new Message('auth.setup_required'), false);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

final class ValidationFailed extends DomainException
{
    /** @param array<string, Message> $errors campo => mensaje */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(new Message('validation_failed'));
    }
}

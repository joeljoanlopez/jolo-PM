<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ValidationFailed extends DomainException
{
    /** @param array<string, string> $errors campo => mensaje */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}

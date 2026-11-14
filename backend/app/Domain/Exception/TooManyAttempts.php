<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

final class TooManyAttempts extends DomainException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct(new Message('auth.too_many_attempts', ['seconds' => $retryAfter]));
    }
}

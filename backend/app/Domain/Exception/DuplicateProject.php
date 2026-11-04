<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

final class DuplicateProject extends DomainException
{
    public function __construct(string $repository)
    {
        parent::__construct(new Message('duplicate_project', ['repository' => $repository]));
    }
}

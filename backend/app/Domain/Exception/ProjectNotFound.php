<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

final class ProjectNotFound extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct(new Message('project_not_found', ['id' => $id]));
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum TaskState: string
{
    case Pending = 'pending';
    /** Sin marcar en la rama principal, marcada en alguna PR abierta. */
    case Review = 'review';
    case Done = 'done';
}

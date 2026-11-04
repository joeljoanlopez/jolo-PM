<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** GitHub no responde, rechaza el token o ha cortado por límite de peticiones. */
final class SourceUnavailable extends DomainException
{
}

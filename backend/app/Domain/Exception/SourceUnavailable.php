<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** GitHub no responde, rechaza el token o ha cortado por límite de peticiones. */
final class SourceUnavailable extends DomainException
{
    public static function missingToken(): self
    {
        return new self(new Message('missing_token'));
    }

    public static function connectionFailed(string $detail, ?\Throwable $previous = null): self
    {
        return new self(new Message('connection_failed', ['detail' => $detail]), $previous);
    }

    public static function tokenRejected(): self
    {
        return new self(new Message('token_rejected'));
    }

    public static function rateLimited(): self
    {
        return new self(new Message('rate_limited'));
    }

    /** @param Message $resource qué se intentaba leer (claves `resource.*`) */
    public static function unexpectedResponse(int $status, Message $resource, string $detail): self
    {
        return new self(new Message('unexpected_response', [
            'status' => $status,
            'resource' => $resource,
            'detail' => $detail,
        ]));
    }
}

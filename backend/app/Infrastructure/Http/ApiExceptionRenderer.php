<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Exception\DomainException;
use App\Domain\Exception\DuplicateProject;
use App\Domain\Exception\ProjectNotFound;
use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Exception\SourceUnavailable;
use App\Domain\Exception\ValidationFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Traduce excepciones a respuestas con forma fija:
 * {"error": {"code": "...", "message": "...", "fields": {...}}}
 */
final class ApiExceptionRenderer
{
    public function __invoke(\Throwable $e): ?JsonResponse
    {
        return match (true) {
            $e instanceof ValidationFailed => self::error(422, 'validation_failed', $e->getMessage(), $e->errors),
            $e instanceof ValidationException => self::error(
                422,
                'validation_failed',
                $e->getMessage(),
                array_map(static fn (array $messages) => $messages[0], $e->errors()),
            ),
            $e instanceof RepositoryNotAccessible => self::error(422, 'repository_not_accessible', $e->getMessage()),
            $e instanceof ProjectNotFound => self::error(404, 'not_found', $e->getMessage()),
            $e instanceof NotFoundHttpException => self::error(404, 'not_found', 'Ruta no encontrada.'),
            $e instanceof DuplicateProject => self::error(409, 'duplicate_project', $e->getMessage()),
            $e instanceof SourceUnavailable => self::error(502, 'source_unavailable', $e->getMessage()),
            $e instanceof DomainException => self::error(400, 'domain_error', $e->getMessage()),
            // Lo demás lo gestiona Laravel (500 con detalle solo si APP_DEBUG).
            default => null,
        };
    }

    /** @param array<string, string> $fields */
    private static function error(int $status, string $code, string $message, array $fields = []): JsonResponse
    {
        $body = ['code' => $code, 'message' => $message];
        if ($fields !== []) {
            $body['fields'] = $fields;
        }

        return response()->json(['error' => $body], $status);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Exception\DomainException;
use App\Domain\Exception\DuplicateProject;
use App\Domain\Exception\ProjectNotFound;
use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Exception\SourceUnavailable;
use App\Domain\Exception\ValidationFailed;
use App\Infrastructure\I18n\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Traduce excepciones a respuestas con forma fija, con los textos en el idioma
 * de la petición:
 * {"error": {"code": "...", "message": "...", "fields": {...}}}
 */
final class ApiExceptionRenderer
{
    public function __invoke(\Throwable $e): ?JsonResponse
    {
        return match (true) {
            $e instanceof ValidationFailed => self::validation(array_map(Translator::translate(...), $e->errors)),
            $e instanceof ValidationException => self::validation(
                array_map(static fn (array $messages) => $messages[0], $e->errors()),
            ),
            $e instanceof RepositoryNotAccessible => self::domain(422, 'repository_not_accessible', $e),
            $e instanceof ProjectNotFound => self::domain(404, 'not_found', $e),
            $e instanceof NotFoundHttpException => self::error(404, 'not_found', __('errors.route_not_found')),
            $e instanceof DuplicateProject => self::domain(409, 'duplicate_project', $e),
            $e instanceof SourceUnavailable => self::domain(502, 'source_unavailable', $e),
            $e instanceof DomainException => self::domain(400, 'domain_error', $e),
            // Lo demás lo gestiona Laravel (500 con detalle solo si APP_DEBUG).
            default => null,
        };
    }

    private static function domain(int $status, string $code, DomainException $e): JsonResponse
    {
        return self::error($status, $code, Translator::translate($e->reason));
    }

    /** @param array<string, string> $fields */
    private static function validation(array $fields): JsonResponse
    {
        return self::error(422, 'validation_failed', implode(' ', $fields), $fields);
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

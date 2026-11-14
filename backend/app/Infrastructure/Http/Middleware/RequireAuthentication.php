<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\SessionStatus;
use App\Domain\Exception\LoginRequired;
use App\Infrastructure\Auth\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sin sesión iniciada (contraseña y segundo factor), 401. */
final class RequireAuthentication
{
    public function __construct(private readonly SessionStatus $status)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->status->isAuthenticated((new AuthSession($request->session()))->authenticatedAccountId())) {
            throw LoginRequired::unauthenticated();
        }

        return $next($request);
    }
}

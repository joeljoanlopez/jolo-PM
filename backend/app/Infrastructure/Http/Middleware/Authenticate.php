<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exception\NotAuthenticated;
use App\Infrastructure\Http\Auth\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Solo pasa con la sesión autenticada (contraseña y segundo factor); si no, 401. */
final class Authenticate
{
    public function __construct(private readonly AuthSession $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->auth->isAuthenticated()) {
            throw NotAuthenticated::noSession();
        }

        return $next($request);
    }
}

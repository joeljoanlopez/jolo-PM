<?php

use App\Infrastructure\Http\ApiExceptionRenderer;
use App\Infrastructure\Http\Middleware\PreventRequestForgery;
use App\Infrastructure\Http\Middleware\UseRequestLanguage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    // Los comandos de consola son adaptadores de entrada: viven en Infrastructure.
    ->withCommands([__DIR__.'/../app/Infrastructure/Console'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(UseRequestLanguage::class);
        // La API la usa solo la web, desde el mismo origen: sesión en cookie
        // (E10) y CSRF con la cookie XSRF-TOKEN y la cabecera X-XSRF-TOKEN.
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PreventRequestForgery::class,
        ]);
        // IP real del cliente detrás de nginx, solo si viene de un proxy de
        // confianza (config/trustedproxy.php). X-Forwarded-Host no: no hace falta
        // y permitiría falsear el host.
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Es una API pura: los errores siempre en JSON, nunca páginas HTML.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
        $exceptions->render(fn (\Throwable $e) => (new ApiExceptionRenderer())($e));
        // Errores de uso esperados (repo sin acceso, validación, login
        // fallido…): no son fallos de la app y no deben llenar el log.
        $exceptions->dontReport([\App\Domain\Exception\DomainException::class]);
    })->create();

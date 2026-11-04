<?php

use App\Infrastructure\Http\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    // Los comandos de consola son adaptadores de entrada: viven en Infrastructure.
    ->withCommands([__DIR__.'/../app/Infrastructure/Console'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Infrastructure\Http\Middleware\UseRequestLanguage::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Es una API pura: los errores siempre en JSON, nunca páginas HTML.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
        $exceptions->render(fn (\Throwable $e) => (new ApiExceptionRenderer())($e));
        // Errores de uso esperados (repo sin acceso, validación…): no son fallos
        // de la app y no deben llenar el log.
        $exceptions->dontReport([\App\Domain\Exception\DomainException::class]);
    })->create();

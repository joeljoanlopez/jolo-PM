<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\I18n\Translator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responde en el idioma de la cabecera Accept-Language (es o en). Sin
 * cabecera, o con un idioma que no hay, en castellano.
 */
final class UseRequestLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($request->getPreferredLanguage(Translator::LOCALES) ?? 'es');

        return $next($request);
    }
}

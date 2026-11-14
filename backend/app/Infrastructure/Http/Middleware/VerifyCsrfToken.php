<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * El CSRF de Laravel: cookie `XSRF-TOKEN` y cabecera `X-XSRF-TOKEN` en cada
 * petición que modifica algo. A diferencia del original, también se comprueba
 * en los tests, para poder probarlo.
 */
final class VerifyCsrfToken extends PreventRequestForgery
{
    protected function runningUnitTests()
    {
        return false;
    }
}

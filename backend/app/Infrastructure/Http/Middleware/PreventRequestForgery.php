<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Base;

/**
 * CSRF de Laravel: pasa la petición si el navegador dice que es del mismo
 * origen (`Sec-Fetch-Site`) o si trae la cabecera `X-XSRF-TOKEN` con el valor
 * de la cookie `XSRF-TOKEN`. A diferencia del original, no se desactiva en los
 * tests: así los tests comprueban la protección de verdad.
 */
final class PreventRequestForgery extends Base
{
    protected function runningUnitTests()
    {
        return false;
    }
}

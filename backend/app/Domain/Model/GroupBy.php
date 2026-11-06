<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Cómo se reparten las casillas en tarjetas: una por fichero (lo de siempre) o
 * una por sección de nivel 2 (`##`), para planes que lo meten todo en un único
 * markdown.
 */
enum GroupBy: string
{
    case File = 'file';
    case Section = 'section';
}

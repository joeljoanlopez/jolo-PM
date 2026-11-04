<?php

declare(strict_types=1);

namespace App\Domain\Service;

/**
 * Globs de rutas al estilo de .gitignore, sin dependencias:
 * - `*`  cualquier cosa dentro de un mismo directorio
 * - `**` cualquier número de directorios (incluido ninguno)
 * - `?`  un carácter que no sea `/`
 */
final class GlobMatcher
{
    /** @var list<string> */
    private array $regexes;

    /** @param list<string> $patterns */
    public function __construct(array $patterns)
    {
        $this->regexes = array_map(self::toRegex(...), $patterns);
    }

    public function matches(string $path): bool
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function toRegex(string $pattern): string
    {
        $pattern = ltrim(trim($pattern), '/');
        $regex = '';
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];
            if ($char === '*' && ($pattern[$i + 1] ?? '') === '*') {
                $i++;
                if (($pattern[$i + 1] ?? '') === '/') {
                    // `**/` también casa con cero directorios: `docs/**/a.md` → `docs/a.md`.
                    $i++;
                    $regex .= '(?:.*/)?';
                } else {
                    $regex .= '.*';
                }
            } elseif ($char === '*') {
                $regex .= '[^/]*';
            } elseif ($char === '?') {
                $regex .= '[^/]';
            } else {
                $regex .= preg_quote($char, '#');
            }
        }

        return '#^' . $regex . '$#';
    }
}

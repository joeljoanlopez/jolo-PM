<?php

declare(strict_types=1);

namespace App\Domain\Service;

/**
 * Genera las anclas de los encabezados como GitHub (github-slugger): minúsculas,
 * fuera la puntuación salvo `-` y `_`, cada espacio a `-`, y `-1`, `-2`… si el
 * ancla ya salió antes en el mismo documento. Una instancia por documento.
 */
final class GitHubAnchors
{
    /** @var array<string, int> */
    private array $seen = [];

    public function next(string $heading): string
    {
        $kept = (string) preg_replace('/[^\p{L}\p{M}\p{N}\p{Pc} -]/u', '', mb_strtolower($heading));
        $base = str_replace(' ', '-', $kept);

        $anchor = $base;
        while (isset($this->seen[$anchor])) {
            $anchor = $base . '-' . ++$this->seen[$base];
        }
        $this->seen[$anchor] = 0;

        return $anchor;
    }
}

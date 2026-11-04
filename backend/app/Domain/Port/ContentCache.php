<?php

declare(strict_types=1);

namespace App\Domain\Port;

/**
 * Caché de contenido de ficheros por SHA de blob. Un SHA identifica siempre el
 * mismo contenido, así que una entrada nunca queda obsoleta: solo se purga lo
 * que lleva tiempo sin usarse.
 */
interface ContentCache
{
    public function get(string $sha): ?string;

    public function put(string $sha, string $content): void;

    /** Borra lo no usado en $days días. Devuelve cuántas entradas. */
    public function prune(int $days): int;
}

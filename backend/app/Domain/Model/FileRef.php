<?php

declare(strict_types=1);

namespace App\Domain\Model;

/** Un fichero de un árbol git, identificado por el SHA de su blob (su contenido). */
final class FileRef
{
    public function __construct(
        public readonly string $path,
        public readonly string $sha,
    ) {
    }
}

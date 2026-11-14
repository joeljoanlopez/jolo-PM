<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Una casilla de un markdown, tal como está en un fichero concreto.
 *
 * `key` identifica la misma tarea en dos versiones del fichero (la rama
 * principal y la de una PR): texto normalizado más el número de aparición, para
 * que dos tareas con el mismo texto no se confundan.
 *
 * `section` es el encabezado más cercano (`##` o más profundo); `chapter` y
 * `chapterAnchor`, el último `##` y su ancla de GitHub, para agrupar por sección.
 */
final class Task
{
    public function __construct(
        public readonly string $key,
        public readonly string $text,
        public readonly bool $checked,
        public readonly int $depth,
        public readonly int $line,
        public readonly ?string $section,
        public readonly ?string $chapter = null,
        public readonly ?string $chapterAnchor = null,
    ) {
    }
}

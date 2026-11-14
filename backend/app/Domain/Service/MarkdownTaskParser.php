<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\ParsedDocument;
use App\Domain\Model\Task;

/**
 * Extrae las casillas de un markdown sin pedir ningún formato especial: vale
 * cualquier elemento de lista (`-`, `*`, `+`, `1.`, `1)`) con `[ ]`, `[x]` o `[X]`.
 */
final class MarkdownTaskParser
{
    private const CHECKBOX = '/^(?<indent>[ \t]*)(?:[-*+]|\d+[.)])[ \t]+\[(?<mark>[ xX])\][ \t]+(?<text>\S.*?)[ \t]*$/';
    private const HEADING = '/^(?<level>#{1,6})[ \t]+(?<text>.*?)[ \t#]*$/';
    private const FENCE = '/^[ \t]{0,3}(?<fence>`{3,}|~{3,})/';

    public function parse(string $path, string $markdown): ParsedDocument
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];

        $title = null;
        $section = null;
        $chapter = $chapterAnchor = null;
        $anchors = new GitHubAnchors();
        $fence = null;
        // Sangrías abiertas de la lista actual, de menor a mayor: la profundidad
        // de una tarea es cuántas hay por debajo de la suya.
        $indentStack = [];
        $occurrences = [];
        $tasks = [];

        foreach ($lines as $index => $line) {
            if (preg_match(self::FENCE, $line, $m)) {
                $marker = $m['fence'];
                if ($fence === null) {
                    $fence = $marker;
                } elseif ($marker[0] === $fence[0] && strlen($marker) >= strlen($fence)) {
                    $fence = null;
                }
                continue;
            }
            if ($fence !== null) {
                continue;
            }

            if (preg_match(self::HEADING, $line, $m)) {
                $text = self::plainText($m['text']);
                $anchor = $anchors->next($text);
                if ($m['level'] === '##') {
                    [$chapter, $chapterAnchor] = [$text, $anchor];
                }
                if ($m['level'] === '#' && $title === null) {
                    $title = $text;
                } elseif ($m['level'] !== '#') {
                    $section = $text;
                }
                $indentStack = [];
                continue;
            }

            if (!preg_match(self::CHECKBOX, $line, $m)) {
                continue;
            }

            $indent = strlen(str_replace("\t", '    ', $m['indent']));
            while ($indentStack !== [] && end($indentStack) >= $indent) {
                array_pop($indentStack);
            }
            $depth = count($indentStack);
            $indentStack[] = $indent;

            $text = $m['text'];
            $normalized = self::normalize($text);
            $occurrences[$normalized] = ($occurrences[$normalized] ?? 0) + 1;

            $tasks[] = new Task(
                key: $normalized . '#' . $occurrences[$normalized],
                text: $text,
                checked: $m['mark'] !== ' ',
                depth: $depth,
                line: $index + 1,
                section: $section,
                chapter: $chapter,
                chapterAnchor: $chapterAnchor,
            );
        }

        return new ParsedDocument($path, $title ?? self::titleFromPath($path), $tasks);
    }

    /**
     * Texto sin formato para comparar: sin enlaces (se queda el texto), sin
     * marcas de énfasis o código, en minúsculas y con espacios colapsados. Así
     * cambiar la URL de un enlace o poner algo en negrita no "crea" otra tarea.
     */
    public static function normalize(string $text): string
    {
        $plain = mb_strtolower(self::plainText($text));

        return trim((string) preg_replace('/\s+/u', ' ', $plain));
    }

    private static function plainText(string $text): string
    {
        $text = (string) preg_replace('/!?\[([^\]]*)\]\([^)]*\)/u', '$1', $text);
        $text = str_replace(['`', '**', '__', '~~'], '', $text);

        return trim((string) preg_replace('/(?<![\w*])[*_](?=\S)(.+?)(?<=\S)[*_](?![\w*])/u', '$1', $text));
    }

    private static function titleFromPath(string $path): string
    {
        return (string) preg_replace('/\.(md|markdown)$/i', '', basename($path));
    }
}

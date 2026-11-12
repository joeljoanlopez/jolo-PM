<?php

/*
 * Falla si la cobertura de líneas de un informe Clover baja del mínimo.
 * PHPUnit no tiene umbral propio. Lo usa `composer coverage`.
 *
 *     php tests/coverage-check.php build/coverage/clover.xml 70
 */

declare(strict_types=1);

$file = $argv[1] ?? 'build/coverage/clover.xml';
$minimum = (float) ($argv[2] ?? 70);

$xml = is_file($file) ? simplexml_load_file($file) : false;
if ($xml === false) {
    fwrite(STDERR, "No se puede leer el informe de cobertura $file\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$lines = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $lines === 0 ? 100.0 : 100 * $covered / $lines;

printf("Cobertura de líneas: %.2f %% (%d/%d). Mínimo: %.0f %%.\n", $percent, $covered, $lines, $minimum);
if ($percent < $minimum) {
    fwrite(STDERR, "La cobertura está por debajo del mínimo.\n");
    exit(1);
}

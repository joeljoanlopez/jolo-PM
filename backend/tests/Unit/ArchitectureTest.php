<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guardián de la arquitectura hexagonal: el dominio no conoce a nadie y la
 * aplicación solo conoce al dominio. Si este test falla, alguien ha metido el
 * framework (o un adaptador) donde no toca.
 */
final class ArchitectureTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> */
    public static function layers(): iterable
    {
        $framework = ['Illuminate\\', 'Symfony\\'];
        $outer = ['App\\Infrastructure\\', 'App\\Providers\\'];

        yield 'Domain' => ['Domain', [...$framework, 'App\\Application\\', ...$outer]];
        yield 'Application' => ['Application', [...$framework, ...$outer]];
    }

    /** @param list<string> $forbidden */
    #[DataProvider('layers')]
    public function test_layer_does_not_depend_on_outer_layers(string $layer, array $forbidden): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . "/../../app/$layer"));
        $checked = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $checked++;
            $source = (string) file_get_contents($file->getPathname());
            foreach ($forbidden as $namespace) {
                $this->assertStringNotContainsString(
                    $namespace,
                    $source,
                    sprintf('%s depende de %s', $file->getFilename(), $namespace),
                );
            }
            // Ni helpers globales de Laravel.
            $this->assertDoesNotMatchRegularExpression(
                '/(?<![\w>:$])(app|config|env|now|response|request|collect)\(/',
                $source,
                sprintf('%s usa un helper global del framework', $file->getFilename()),
            );
        }

        $this->assertGreaterThan(0, $checked);
    }
}

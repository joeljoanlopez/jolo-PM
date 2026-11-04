<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Service\GlobMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GlobMatcherTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function cases(): iterable
    {
        yield 'asterisco en un directorio' => ['docs/learnPlan/*.md', 'docs/learnPlan/01a-http.md', true];
        yield 'asterisco no cruza directorios' => ['docs/*.md', 'docs/learnPlan/01.md', false];
        yield 'doble asterisco cruza' => ['docs/**/*.md', 'docs/a/b/c.md', true];
        yield 'doble asterisco admite cero' => ['docs/**/*.md', 'docs/c.md', true];
        yield 'doble asterisco al principio' => ['**/*.md', 'README.md', true];
        yield 'fichero exacto' => ['TODO.md', 'TODO.md', true];
        yield 'fichero exacto no es prefijo' => ['TODO.md', 'docs/TODO.md', false];
        yield 'interrogación' => ['docs/plan/E0?-*.md', 'docs/plan/E01-fundacion.md', true];
        yield 'barra inicial se ignora' => ['/docs/*.md', 'docs/a.md', true];
        yield 'puntos literales' => ['a.md', 'abmd', false];
    }

    #[DataProvider('cases')]
    public function test_matches(string $pattern, string $path, bool $expected): void
    {
        $this->assertSame($expected, (new GlobMatcher([$pattern]))->matches($path));
    }

    public function test_any_of_several_patterns(): void
    {
        $matcher = new GlobMatcher(['docs/*.md', 'TODO.md']);

        $this->assertTrue($matcher->matches('TODO.md'));
        $this->assertFalse($matcher->matches('README.md'));
    }
}

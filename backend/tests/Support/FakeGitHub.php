<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Port\ContentCache;
use App\Domain\Port\SourceHost;
use App\Infrastructure\Cache\CachingSourceHost;

/**
 * Cambia GitHub por FakeSourceHost con un plan de dos épicas en `docs/plan`.
 * La caché de contenidos que lo envuelve es la de verdad.
 */
trait FakeGitHub
{
    private FakeSourceHost $github;

    private function fakeGitHub(): void
    {
        $this->github = new FakeSourceHost();
        $this->github->files = [
            'docs/plan/E01.md' => "# E01\n## Subtareas\n- [x] a\n- [ ] b",
            'docs/plan/E02.md' => "# E02\n- [ ] c",
            'README.md' => '- [ ] fuera del glob',
        ];
        $this->app->instance(
            SourceHost::class,
            new CachingSourceHost($this->github, $this->app->make(ContentCache::class)),
        );
    }

    /** Desde aquí, cualquier lectura de GitHub falla con un timeout. */
    private function breakGitHub(): void
    {
        $this->app->instance(SourceHost::class, new FailingSourceHost($this->github));
    }
}

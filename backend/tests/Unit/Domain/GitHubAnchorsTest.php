<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Service\GitHubAnchors;
use PHPUnit\Framework\TestCase;

final class GitHubAnchorsTest extends TestCase
{
    public function test_follows_github_rules(): void
    {
        $anchors = new GitHubAnchors();

        $this->assertSame('criterios-de-aceptación', $anchors->next('Criterios de aceptación'));
        $this->assertSame('e092--tarjeta-por-sección', $anchors->next('E09.2 — Tarjeta por Sección'));
        $this->assertSame('api-y-front_end', $anchors->next('API y front_end!'));
        $this->assertSame('', $anchors->next('¿?'));
    }

    public function test_repeated_headings_get_a_numeric_suffix(): void
    {
        $anchors = new GitHubAnchors();

        $this->assertSame(
            ['tareas', 'tareas-1', 'tareas-2'],
            [$anchors->next('Tareas'), $anchors->next('Tareas'), $anchors->next('tareas')],
        );
    }
}

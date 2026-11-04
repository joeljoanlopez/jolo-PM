<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Model\Project;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function test_normalizes_user_input(): void
    {
        $project = Project::fromInput(null, ' https://github.com/joeljoanlopez/PKManagement.git ', ' ', "docs/*.md\n\n /TODO.md \ndocs/*.md", '');

        $this->assertSame('joeljoanlopez/PKManagement', $project->fullName());
        $this->assertNull($project->branch);
        $this->assertSame(['docs/*.md', 'TODO.md'], $project->paths);
        $this->assertNull($project->displayName);
        $this->assertSame('PKManagement', $project->label());
    }

    public function test_rejects_invalid_repository_and_empty_paths(): void
    {
        try {
            Project::fromInput(null, 'no-es-un-repo', 'rama mala', [], null);
            $this->fail('Debería fallar');
        } catch (ValidationFailed $e) {
            $this->assertSame(['repository', 'branch', 'paths'], array_keys($e->errors));
        }
    }
}

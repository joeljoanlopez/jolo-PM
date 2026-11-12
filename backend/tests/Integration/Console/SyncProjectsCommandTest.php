<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Application\AddProject;
use App\Domain\Port\BoardStore;
use Tests\Integration\IntegrationTestCase;
use Tests\Support\FakeGitHub;

final class SyncProjectsCommandTest extends IntegrationTestCase
{
    use FakeGitHub;

    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGitHub();
        $this->projectId = (int) $this->app->make(AddProject::class)
            ->execute('o/r', null, ['docs/plan/*.md'], null)
            ->id;
    }

    public function test_syncing_everything_saves_each_board(): void
    {
        $this->github->files['docs/plan/E02.md'] = "# E02\n- [x] c";

        $this->artisan('projects:sync')
            ->expectsOutput('✓ o/r')
            ->assertSuccessful();

        $this->assertSame(2, $this->storedBoard()['summary']['done'] ?? null);
    }

    public function test_syncing_one_project_reports_its_cards(): void
    {
        $this->artisan('projects:sync', ['id' => $this->projectId])
            ->expectsOutput("Proyecto {$this->projectId}: 2 tarjetas.")
            ->assertSuccessful();
    }

    public function test_an_unknown_project_fails_with_a_translated_message(): void
    {
        $this->artisan('projects:sync', ['id' => 999])
            ->expectsOutput('No existe el proyecto 999.')
            ->assertFailed();
    }

    public function test_a_failing_sync_exits_with_error_and_keeps_the_previous_board(): void
    {
        $this->breakGitHub();

        $this->artisan('projects:sync')
            ->expectsOutput('✗ o/r: No se puede conectar con GitHub: timeout')
            ->assertFailed();

        $stored = $this->app->make(BoardStore::class)->load($this->projectId);
        $this->assertSame(3, $stored['board']['summary']['total'] ?? null);
        $this->assertSame('connection_failed', $stored['error']->key ?? null);
    }

    /** @return array<string, mixed> */
    private function storedBoard(): array
    {
        return $this->app->make(BoardStore::class)->load($this->projectId)['board'] ?? [];
    }
}

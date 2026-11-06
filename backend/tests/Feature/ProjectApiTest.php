<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Port\SourceHost;
use App\Infrastructure\Cache\CachingSourceHost;
use App\Domain\Port\ContentCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSourceHost;
use Tests\TestCase;

final class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private FakeSourceHost $github;

    protected function setUp(): void
    {
        parent::setUp();
        $this->github = new FakeSourceHost();
        $this->github->files = [
            'docs/plan/E01.md' => "# E01\n## Subtareas\n- [x] a\n- [ ] b",
            'docs/plan/E02.md' => "# E02\n- [ ] c",
            'README.md' => '- [ ] fuera del glob',
        ];
        // El adaptador real se cambia por el falso; la caché es la de verdad.
        $this->app->instance(SourceHost::class, new CachingSourceHost($this->github, $this->app->make(ContentCache::class)));
    }

    public function test_health(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_add_project_syncs_and_returns_board(): void
    {
        $response = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']]);

        $response->assertCreated()
            ->assertJsonPath('project.repository', 'o/r')
            ->assertJsonPath('board.summary.total', 3)
            ->assertJsonPath('board.summary.columns.in_progress', 1)
            ->assertJsonPath('board.cards.0.title', 'E01')
            ->assertJsonPath('board.cards.0.tasks.1.section', 'Subtareas')
            ->assertJsonCount(2, 'board.cards');
    }

    public function test_listing_includes_summary(): void
    {
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md'], 'name' => 'Mi plan']);

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('0.label', 'Mi plan')
            ->assertJsonPath('0.summary.done', 1)
            ->assertJsonPath('0.error', null);
    }

    public function test_pr_checking_a_task_moves_card_to_review(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');

        $this->github->openPullRequest(12, ['docs/plan/E02.md' => "# E02\n- [x] c"]);

        $this->postJson("/api/projects/$id/sync")
            ->assertOk()
            ->assertJsonPath('board.cards.1.status', 'review')
            ->assertJsonPath('board.cards.1.tasks.0.prs', [12])
            ->assertJsonPath('board.cards.1.pullRequests.0.number', 12);
    }

    public function test_second_sync_uses_cached_contents(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');
        $this->github->blobRequests = [];

        $this->postJson("/api/projects/$id/sync")->assertOk();

        $this->assertSame([], $this->github->blobRequests);
    }

    public function test_update_and_delete(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');

        $this->patchJson("/api/projects/$id", ['paths' => ['**/*.md'], 'branch' => 'main'])
            ->assertOk()
            ->assertJsonPath('project.branch', 'main')
            ->assertJsonPath('board.summary.total', 4);

        $this->deleteJson("/api/projects/$id")->assertNoContent();
        $this->getJson("/api/projects/$id/board")->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_projects_group_by_file_by_default(): void
    {
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])
            ->assertCreated()
            ->assertJsonPath('project.groupBy', 'file')
            ->assertJsonPath('board.cards.0.key', 'docs/plan/E01.md')
            ->assertJsonCount(2, 'board.cards');
    }

    public function test_group_by_section_is_saved_and_editable(): void
    {
        $this->github->files['docs/plan/PLAN.md'] = "# Plan\n- [ ] suelta\n## A\n- [x] a\n## B\n- [ ] b";

        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/PLAN.md'], 'groupBy' => 'section'])
            ->assertCreated()
            ->assertJsonPath('project.groupBy', 'section')
            ->assertJsonPath('board.cards.*.key', ['docs/plan/PLAN.md', 'docs/plan/PLAN.md#a', 'docs/plan/PLAN.md#b'])
            ->json('project.id');

        // Sin groupBy en la edición se conserva el que había.
        $this->patchJson("/api/projects/$id", ['paths' => ['docs/plan/PLAN.md']])
            ->assertOk()
            ->assertJsonPath('project.groupBy', 'section');

        $this->patchJson("/api/projects/$id", ['paths' => ['docs/plan/PLAN.md'], 'groupBy' => 'file'])
            ->assertOk()
            ->assertJsonPath('project.groupBy', 'file')
            ->assertJsonCount(1, 'board.cards');

        $this->getJson('/api/projects')->assertJsonPath('0.groupBy', 'file');
    }

    public function test_unknown_group_by_is_rejected(): void
    {
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['*.md'], 'groupBy' => 'carpeta'])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.groupBy', fn ($m) => is_string($m) && $m !== '');
    }

    public function test_errors_have_a_stable_shape(): void
    {
        $this->postJson('/api/projects', ['repository' => 'otro/repo', 'paths' => ['*.md']])
            ->assertStatus(422)->assertJsonPath('error.code', 'repository_not_accessible');

        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => []])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['   ']])
            ->assertStatus(422)->assertJsonPath('error.fields.paths', fn ($m) => str_contains($m, 'glob'));

        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['*.md']])->assertCreated();
        $this->postJson('/api/projects', ['repository' => 'https://github.com/o/r', 'paths' => ['*.md']])
            ->assertStatus(409)->assertJsonPath('error.code', 'duplicate_project');
    }

    public function test_failed_sync_keeps_previous_board(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');

        // La rama desaparece: GitHub respondería 404 al pedir el árbol.
        $this->app->instance(SourceHost::class, new class ($this->github) extends \Tests\Support\FailingSourceHost {});

        $this->postJson("/api/projects/$id/sync")->assertStatus(502);

        $this->getJson("/api/projects/$id/board")
            ->assertOk()
            ->assertJsonPath('board.summary.total', 3)
            ->assertJsonPath('error', fn ($e) => str_contains((string) $e, 'GitHub'));
    }

    public function test_errors_follow_the_request_language(): void
    {
        $this->postJson('/api/projects', ['repository' => 'otro/repo', 'paths' => ['*.md']], ['Accept-Language' => 'en-GB,en;q=0.9'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'GitHub cannot find the repository otro/repo or the token has no access to it.');

        $this->postJson('/api/projects', ['paths' => ['*.md']], ['Accept-Language' => 'en'])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.repository', 'The repository field is required.');

        // Sin cabecera, o con un idioma que no hay, en castellano.
        $this->postJson('/api/projects', ['paths' => ['*.md']], ['Accept-Language' => 'fr'])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.repository', 'Falta el campo repositorio.');
        $this->getJson('/api/projects/999/board', ['Accept-Language' => ''])
            ->assertJsonPath('error.message', 'No existe el proyecto 999.');
    }

    public function test_saved_sync_error_is_translated_when_read(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');
        $this->app->instance(SourceHost::class, new class ($this->github) extends \Tests\Support\FailingSourceHost {});
        $this->postJson("/api/projects/$id/sync");

        $this->getJson("/api/projects/$id/board", ['Accept-Language' => 'en'])
            ->assertJsonPath('error', 'Cannot connect to GitHub: timeout');
        $this->getJson('/api/projects', ['Accept-Language' => 'es'])
            ->assertJsonPath('0.error', 'No se puede conectar con GitHub: timeout');
    }

    public function test_errors_saved_before_translations_are_shown_as_is(): void
    {
        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])->json('project.id');
        \App\Infrastructure\Persistence\Models\BoardSnapshot::where('project_id', $id)->update(['error' => 'Texto antiguo.']);

        $this->getJson("/api/projects/$id/board", ['Accept-Language' => 'en'])->assertJsonPath('error', 'Texto antiguo.');
    }
}

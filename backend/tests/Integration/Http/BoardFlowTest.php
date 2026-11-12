<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Illuminate\Testing\TestResponse;
use Tests\Integration\IntegrationTestCase;
use Tests\Support\FakeGitHub;

/**
 * Aceptación de extremo a extremo: solo la API HTTP, con GitHub falso y todo
 * lo demás de verdad (SQLite en fichero, caché, casos de uso, traducciones).
 *
 * Las peticiones sin sesión se probarán con el login (E10): hoy la API no
 * tiene sesión.
 */
final class BoardFlowTest extends IntegrationTestCase
{
    use FakeGitHub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGitHub();
        // Las peticiones de prueba de Symfony llevan `en-us` si no se dice nada.
        $this->withHeader('Accept-Language', 'es');
    }

    public function test_adding_a_project_syncs_it_and_shows_its_cards_in_their_columns(): void
    {
        $this->getJson('/api/repositories')->assertOk()->assertJsonPath('0.fullName', 'o/r');

        $id = $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])
            ->assertCreated()
            ->json('project.id');

        $this->assertSame(
            ['docs/plan/E01.md' => 'in_progress', 'docs/plan/E02.md' => 'todo'],
            $this->columns($this->getJson("/api/projects/$id/board")->assertOk()),
        );
        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.repository', 'o/r')
            ->assertJsonPath('0.summary.total', 3)
            ->assertJsonPath('0.summary.done', 1);
    }

    public function test_a_pull_request_moves_the_card_to_review_until_it_is_merged(): void
    {
        $id = $this->addPlan();

        $this->github->openPullRequest(7, ['docs/plan/E02.md' => "# E02\n- [x] c"]);
        $board = $this->postJson("/api/projects/$id/sync")
            ->assertOk()
            ->assertJsonPath('board.cards.1.pullRequests.0.number', 7);
        $this->assertSame('review', $this->columns($board)['docs/plan/E02.md']);

        // Merge: la PR se cierra y el cambio llega a la rama principal.
        $this->github->pullRequests = [];
        $this->github->files['docs/plan/E02.md'] = "# E02\n- [x] c";
        $board = $this->postJson("/api/projects/$id/sync")->assertOk();
        $this->assertSame('done', $this->columns($board)['docs/plan/E02.md']);
    }

    public function test_editing_a_project_recalculates_its_board_and_deleting_it_removes_everything(): void
    {
        $this->github->files['docs/plan/PLAN.md'] = "# Plan\n## A\n- [x] a\n## B\n- [ ] b";
        $id = $this->addPlan();

        $board = $this->patchJson("/api/projects/$id", [
            'paths' => ['docs/plan/PLAN.md'],
            'groupBy' => 'section',
            'name' => 'Plan único',
        ])->assertOk()->assertJsonPath('project.label', 'Plan único');
        $this->assertSame(
            ['docs/plan/PLAN.md#a' => 'done', 'docs/plan/PLAN.md#b' => 'todo'],
            $this->columns($board),
        );

        $this->deleteJson("/api/projects/$id")->assertNoContent();

        $this->getJson('/api/projects')->assertOk()->assertExactJson([]);
        $this->getJson("/api/projects/$id/board")->assertNotFound();
        $this->assertDatabaseMissing('board_snapshots', ['project_id' => $id]);
    }

    public function test_when_github_fails_the_last_good_board_stays_until_it_recovers(): void
    {
        $id = $this->addPlan();

        $this->breakGitHub();
        $this->postJson("/api/projects/$id/sync")
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'source_unavailable');
        $this->getJson("/api/projects/$id/board")
            ->assertOk()
            ->assertJsonPath('board.summary.total', 3)
            ->assertJsonPath('error', 'No se puede conectar con GitHub: timeout');

        $this->fakeGitHub();
        $this->postJson("/api/projects/$id/sync")->assertOk()->assertJsonPath('error', null);
    }

    public function test_invalid_requests_get_a_stable_error_and_change_nothing(): void
    {
        $id = $this->addPlan();

        $this->postJson('/api/projects', ['paths' => 'no es una lista'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['message', 'fields' => ['repository', 'paths']]]);
        $this->postJson('/api/projects', ['repository' => 'nadie/nada', 'paths' => ['*.md']])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'repository_not_accessible');
        $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['*.md']])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'duplicate_project');
        $this->patchJson("/api/projects/$id", ['repository' => 'otro/repo', 'paths' => ['*.md']])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.repository', fn ($m) => is_string($m) && $m !== '');

        foreach (
            [
                ['patch', '/api/projects/999', ['paths' => ['*.md']]],
                ['delete', '/api/projects/999', []],
                ['post', '/api/projects/999/sync', []],
                ['get', '/api/projects/999/board', []],
            ] as [$method, $uri, $data]
        ) {
            $this->json($method, $uri, $data)->assertNotFound()->assertJsonPath('error.code', 'not_found');
        }
        $this->getJson('/api/no-existe')->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->getJson('/api/projects')
            ->assertJsonCount(1)
            ->assertJsonPath('0.paths', ['docs/plan/*.md']);
    }

    private function addPlan(): int
    {
        return $this->postJson('/api/projects', ['repository' => 'o/r', 'paths' => ['docs/plan/*.md']])
            ->assertCreated()
            ->json('project.id');
    }

    /** @return array<string, string> clave de tarjeta => columna */
    private function columns(TestResponse $response): array
    {
        return array_column($response->json('board.cards'), 'status', 'key');
    }
}

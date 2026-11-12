<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Domain\Model\Board;
use App\Domain\Model\Message;
use App\Domain\Model\Project;
use App\Infrastructure\Persistence\EloquentBoardStore;
use App\Infrastructure\Persistence\EloquentProjectRepository;
use App\Infrastructure\Persistence\Models\BoardSnapshot;
use Tests\Integration\IntegrationTestCase;

final class EloquentBoardStoreTest extends IntegrationTestCase
{
    private EloquentBoardStore $boards;

    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boards = new EloquentBoardStore();
        $this->projectId = (int) (new EloquentProjectRepository())
            ->save(new Project(null, 'o', 'r', null, ['*.md'], null))
            ->id;
    }

    public function test_a_project_never_synced_has_no_board(): void
    {
        $this->assertNull($this->boards->load($this->projectId));
    }

    public function test_a_saved_board_is_read_back_as_plain_arrays(): void
    {
        $at = new \DateTimeImmutable('2026-11-10T09:30:00+00:00');

        $this->boards->saveBoard($this->projectId, new Board('main', 'https://github.com/o/r', [], $at));

        $stored = $this->boards->load($this->projectId);
        $this->assertNotNull($stored);
        $this->assertSame('main', $stored['board']['branch'] ?? null);
        $this->assertSame(0, $stored['board']['summary']['total'] ?? null);
        $this->assertNull($stored['error']);
        $this->assertSame('2026-11-10T09:30:00+00:00', $stored['attemptedAt']);
    }

    public function test_an_error_keeps_the_previous_board_and_the_next_board_clears_it(): void
    {
        $this->boards->saveBoard($this->projectId, $this->board());
        $error = new Message('source.unavailable', ['detail' => 'timeout']);

        $this->boards->saveError($this->projectId, $error, new \DateTimeImmutable());

        $stored = $this->boards->load($this->projectId);
        $this->assertSame('main', $stored['board']['branch'] ?? null);
        $this->assertEquals($error, $stored['error'] ?? null);

        $this->boards->saveBoard($this->projectId, $this->board());
        $stored = $this->boards->load($this->projectId);
        $this->assertNotNull($stored);
        $this->assertNull($stored['error']);
    }

    public function test_an_error_before_any_board_is_saved_without_board(): void
    {
        $nested = new Message('resource.repository', ['repository' => 'o/r']);

        $error = new Message('wrapper', ['what' => $nested]);
        $this->boards->saveError($this->projectId, $error, new \DateTimeImmutable());

        $stored = $this->boards->load($this->projectId);
        $this->assertNull($stored['board'] ?? null);
        $this->assertEquals(new Message('wrapper', ['what' => $nested]), $stored['error'] ?? null);
    }

    public function test_errors_saved_as_plain_text_are_read_as_legacy_messages(): void
    {
        BoardSnapshot::create(['project_id' => $this->projectId, 'error' => 'Texto antiguo.']);

        $this->assertEquals(
            new Message('legacy', ['text' => 'Texto antiguo.']),
            $this->boards->load($this->projectId)['error'] ?? null,
        );
    }

    private function board(): Board
    {
        return new Board('main', 'https://github.com/o/r', [], new \DateTimeImmutable());
    }
}

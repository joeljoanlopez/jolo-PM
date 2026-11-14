<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Domain\Exception\DuplicateProject;
use App\Domain\Exception\ProjectNotFound;
use App\Domain\Model\Board;
use App\Domain\Model\GroupBy;
use App\Domain\Model\Project;
use App\Infrastructure\Persistence\EloquentBoardStore;
use App\Infrastructure\Persistence\EloquentProjectRepository;
use Tests\Integration\IntegrationTestCase;

final class EloquentProjectRepositoryTest extends IntegrationTestCase
{
    private EloquentProjectRepository $projects;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projects = new EloquentProjectRepository();
    }

    public function test_saving_a_new_project_assigns_an_id_and_keeps_every_field(): void
    {
        $saved = $this->projects->save(
            new Project(null, 'o', 'r', 'develop', ['docs/*.md', 'TODO.md'], 'Mi plan', GroupBy::Section),
        );

        $this->assertNotNull($saved->id);
        $read = $this->projects->get($saved->id);
        $this->assertSame(
            ['o', 'r', 'develop', ['docs/*.md', 'TODO.md'], 'Mi plan', GroupBy::Section],
            [$read->owner, $read->name, $read->branch, $read->paths, $read->displayName, $read->groupBy],
        );
    }

    public function test_saving_with_an_id_updates_the_same_row(): void
    {
        $id = $this->save('o', 'r')->id;

        $this->projects->save(new Project($id, 'o', 'r', null, ['*.md'], 'Otro nombre'));

        $this->assertCount(1, $this->projects->all());
        $this->assertSame('Otro nombre', $this->projects->get((int) $id)->displayName);
        $this->assertNull($this->projects->get((int) $id)->branch);
    }

    public function test_all_is_sorted_by_owner_and_name(): void
    {
        $this->save('zeta', 'a');
        $this->save('alfa', 'z');
        $this->save('alfa', 'b');

        $this->assertSame(
            ['alfa/b', 'alfa/z', 'zeta/a'],
            array_map(fn (Project $p) => $p->fullName(), $this->projects->all()),
        );
    }

    public function test_the_same_repository_cannot_be_added_twice(): void
    {
        $this->save('o', 'r');

        $this->expectException(DuplicateProject::class);
        $this->save('o', 'r');
    }

    public function test_a_project_cannot_be_renamed_to_another_existing_repository(): void
    {
        $this->save('o', 'r');
        $other = $this->save('o', 'otro');

        $this->expectException(DuplicateProject::class);
        $this->projects->save(new Project($other->id, 'o', 'r', null, ['*.md'], null));
    }

    public function test_reading_or_updating_a_missing_project_fails(): void
    {
        try {
            $this->projects->get(999);
            $this->fail('get() debería fallar');
        } catch (ProjectNotFound) {
        }

        $this->expectException(ProjectNotFound::class);
        $this->projects->save(new Project(999, 'o', 'r', null, ['*.md'], null));
    }

    public function test_deleting_a_project_also_deletes_its_board(): void
    {
        $id = (int) $this->save('o', 'r')->id;
        $boards = new EloquentBoardStore();
        $boards->saveBoard($id, new Board('main', 'https://github.com/o/r', [], new \DateTimeImmutable()));

        $this->projects->delete($id);

        $this->assertSame([], $this->projects->all());
        $this->assertNull($boards->load($id));
    }

    private function save(string $owner, string $name): Project
    {
        return $this->projects->save(new Project(null, $owner, $name, null, ['*.md'], null));
    }
}

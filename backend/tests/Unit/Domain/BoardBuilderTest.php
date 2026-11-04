<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Model\PullRequest;
use App\Domain\Service\BoardBuilder;
use App\Domain\Service\MarkdownTaskParser;
use PHPUnit\Framework\TestCase;

final class BoardBuilderTest extends TestCase
{
    private MarkdownTaskParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownTaskParser();
    }

    public function test_column_follows_checkboxes_on_main_branch(): void
    {
        $board = $this->build([
            'todo.md' => "- [ ] a\n- [ ] b",
            'doing.md' => "- [x] a\n- [ ] b",
            'done.md' => "- [x] a\n- [x] b",
            'sin-tareas.md' => "# Solo texto",
        ]);

        $this->assertSame(
            ['doing.md' => 'in_progress', 'done.md' => 'done', 'todo.md' => 'todo'],
            array_column(array_map(fn ($c) => ['p' => $c->path, 's' => $c->status->value], $board->cards), 's', 'p'),
        );
    }

    public function test_task_checked_in_open_pr_is_in_review(): void
    {
        $pr = new PullRequest(7, 'Cap 1', 'https://github.com/o/r/pull/7', false, 'abc');
        $board = $this->build(
            ['plan.md' => "- [x] a\n- [ ] b\n- [ ] c"],
            ['plan.md' => [[$pr, "- [x] a\n- [x] b\n- [ ] c"]]],
        );

        $card = $board->cards[0];
        $this->assertSame('review', $card->status->value);
        $this->assertSame([1, 1], [$card->done, $card->review]);
        $this->assertSame(['done', 'review', 'pending'], array_map(fn ($t) => $t['state']->value, $card->tasks));
        $this->assertSame([7], $card->tasks[1]['prs']);
        $this->assertSame([$pr], $card->pullRequests);
    }

    public function test_pr_that_touches_file_without_checking_tasks_is_ignored(): void
    {
        $pr = new PullRequest(3, 'Typo', 'u', false, 'abc');
        $board = $this->build(
            ['plan.md' => "- [ ] a"],
            ['plan.md' => [[$pr, "Arreglo una errata\n- [ ] a"]]],
        );

        $this->assertSame('todo', $board->cards[0]->status->value);
        $this->assertSame([], $board->cards[0]->pullRequests);
    }

    public function test_done_wins_over_review(): void
    {
        $pr = new PullRequest(1, 'x', 'u', false, 'abc');
        $board = $this->build(['plan.md' => '- [x] a'], ['plan.md' => [[$pr, '- [x] a']]]);

        $this->assertSame('done', $board->cards[0]->status->value);
    }

    public function test_summary_counts_columns_and_tasks(): void
    {
        $summary = $this->build(['a.md' => "- [x] a\n- [ ] b", 'b.md' => '- [ ] c'])->summary();

        $this->assertSame(['total' => 3, 'done' => 1, 'review' => 0], array_slice($summary, 0, 3));
        $this->assertSame(['todo' => 1, 'in_progress' => 1, 'review' => 0, 'done' => 0], $summary['columns']);
    }

    public function test_card_url_points_to_file_on_branch(): void
    {
        $board = $this->build(['docs/plan/E01 x.md' => '- [ ] a']);

        $this->assertSame('https://github.com/o/r/blob/main/docs/plan/E01%20x.md', $board->cards[0]->url);
    }

    /**
     * @param array<string, string> $files
     * @param array<string, list<array{PullRequest, string}>> $prFiles
     */
    private function build(array $files, array $prFiles = []): \App\Domain\Model\Board
    {
        $documents = [];
        foreach ($files as $path => $markdown) {
            $documents[] = $this->parser->parse($path, $markdown);
        }
        $versions = [];
        foreach ($prFiles as $path => $list) {
            foreach ($list as [$pr, $markdown]) {
                $versions[$path][] = ['pr' => $pr, 'document' => $this->parser->parse($path, $markdown)];
            }
        }

        return (new BoardBuilder())->build('https://github.com/o/r', 'main', $documents, $versions, new \DateTimeImmutable());
    }
}

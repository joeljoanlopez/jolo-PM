<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Model\GroupBy;
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

    public function test_file_mode_keys_cards_by_path(): void
    {
        $board = $this->build(['plan.md' => "# Plan\n## A\n- [ ] a\n## B\n- [ ] b"]);

        $this->assertCount(1, $board->cards);
        $this->assertSame('plan.md', $board->cards[0]->key);
        $this->assertSame('Plan', $board->cards[0]->title);
    }

    public function test_section_mode_makes_one_card_per_level_two_heading(): void
    {
        $plan = "# Plan\n";
        foreach (['Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco'] as $i => $name) {
            $plan .= "## $name\n- [x] hecho $i\n- [ ] pendiente $i\n";
        }
        $board = $this->build(['PLAN.md' => $plan], [], GroupBy::Section);

        $this->assertSame(['Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco'], array_map(fn ($c) => $c->title, $board->cards));
        $this->assertSame(['PLAN.md#uno', 'PLAN.md#dos', 'PLAN.md#tres', 'PLAN.md#cuatro', 'PLAN.md#cinco'], array_map(fn ($c) => $c->key, $board->cards));
        $this->assertSame(['PLAN.md'], array_values(array_unique(array_map(fn ($c) => $c->path, $board->cards))));
        $this->assertSame([2, 1], [$board->cards[0]->total, $board->cards[0]->done]);
        $this->assertSame('in_progress', $board->cards[0]->status->value);
    }

    public function test_section_status_follows_its_own_checkboxes(): void
    {
        $board = $this->build(['plan.md' => "## Hecha\n- [x] a\n## Por hacer\n- [ ] b\n## A medias\n- [x] c\n- [ ] d"], [], GroupBy::Section);

        $this->assertSame(['done', 'todo', 'in_progress'], array_map(fn ($c) => $c->status->value, $board->cards));
    }

    public function test_checkboxes_before_first_section_go_to_a_card_titled_like_the_file(): void
    {
        $board = $this->build([
            'docs/plan.md' => "# Mi plan\n- [ ] suelta\n### Detalle\n- [x] sin sección de nivel 2\n## Sección\n- [ ] a",
            'notas.md' => "- [ ] sin título\n## S\n- [ ] b",
        ], [], GroupBy::Section);

        $this->assertSame(
            ['docs/plan.md' => 'Mi plan', 'docs/plan.md#sección' => 'Sección', 'notas.md' => 'notas', 'notas.md#s' => 'S'],
            array_column(array_map(fn ($c) => ['k' => $c->key, 't' => $c->title], $board->cards), 't', 'k'),
        );
        $this->assertSame(['suelta', 'sin sección de nivel 2'], array_column($board->cards[0]->tasks, 'text'));
        $this->assertSame('https://github.com/o/r/blob/main/docs/plan.md', $board->cards[0]->url);
    }

    public function test_subsections_stay_in_their_level_two_card(): void
    {
        $board = $this->build(['plan.md' => "## Backend\n- [x] a\n### API\n- [ ] b\n## Frontend\n- [ ] c"], [], GroupBy::Section);

        $this->assertSame(['Backend', 'Frontend'], array_map(fn ($c) => $c->title, $board->cards));
        $this->assertSame(['Backend', 'API'], array_column($board->cards[0]->tasks, 'section'));
    }

    public function test_sections_without_checkboxes_have_no_card(): void
    {
        $board = $this->build(['plan.md' => "## Objetivo\nTexto\n## Subtareas\n- [ ] a"], [], GroupBy::Section);

        $this->assertSame(['plan.md#subtareas'], array_map(fn ($c) => $c->key, $board->cards));
    }

    public function test_section_card_url_has_github_anchor(): void
    {
        $board = $this->build(['docs/E09 x.md' => "# E09.2 — Tarjeta\n## Criterios de aceptación\n- [ ] a"], [], GroupBy::Section);

        $this->assertSame('https://github.com/o/r/blob/main/docs/E09%20x.md#criterios-de-aceptación', $board->cards[0]->url);
    }

    public function test_repeated_section_names_get_distinct_keys(): void
    {
        $board = $this->build(['plan.md' => "## Tareas\n- [ ] a\n## Tareas\n- [ ] b"], [], GroupBy::Section);

        $this->assertSame(['plan.md#tareas', 'plan.md#tareas-1'], array_map(fn ($c) => $c->key, $board->cards));
        $this->assertStringEndsWith('#tareas-1', $board->cards[1]->url);
    }

    public function test_pr_checking_a_task_moves_only_its_section_to_review(): void
    {
        $pr = new PullRequest(9, 'B', 'u', false, 'abc');
        $board = $this->build(
            ['plan.md' => "## A\n- [ ] a\n## B\n- [ ] b"],
            ['plan.md' => [[$pr, "## A\n- [ ] a\n## B\n- [x] b"]]],
            GroupBy::Section,
        );

        $this->assertSame(['todo', 'review'], array_map(fn ($c) => $c->status->value, $board->cards));
        $this->assertSame([], $board->cards[0]->pullRequests);
        $this->assertSame([$pr], $board->cards[1]->pullRequests);
    }

    /**
     * @param array<string, string> $files
     * @param array<string, list<array{PullRequest, string}>> $prFiles
     */
    private function build(array $files, array $prFiles = [], GroupBy $groupBy = GroupBy::File): \App\Domain\Model\Board
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

        return (new BoardBuilder())->build('https://github.com/o/r', 'main', $documents, $versions, new \DateTimeImmutable(), $groupBy);
    }
}

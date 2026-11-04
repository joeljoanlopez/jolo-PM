<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Service\MarkdownTaskParser;
use PHPUnit\Framework\TestCase;

final class MarkdownTaskParserTest extends TestCase
{
    private MarkdownTaskParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownTaskParser();
    }

    public function test_reads_checkboxes_with_any_list_marker(): void
    {
        $doc = $this->parser->parse('a.md', "- [ ] uno\n* [x] dos\n+ [X] tres\n1. [ ] cuatro\n2) [x] cinco\n- no es tarea\n-[ ] tampoco");

        $this->assertSame(['uno', 'dos', 'tres', 'cuatro', 'cinco'], array_map(fn ($t) => $t->text, $doc->tasks));
        $this->assertSame([false, true, true, false, true], array_map(fn ($t) => $t->checked, $doc->tasks));
    }

    public function test_title_is_first_h1_or_file_name(): void
    {
        $this->assertSame('Cap 05 — API', $this->parser->parse('x/05.md', "# Cap 05 — API\n# Otro")->title);
        $this->assertSame('05-api', $this->parser->parse('x/05-api.md', "## Sin h1")->title);
    }

    public function test_tasks_remember_section_line_and_depth(): void
    {
        $markdown = <<<MD
        # Título
        ## Ejercicios
        - [ ] padre
          - [x] hijo
            - [ ] nieto
          - [ ] otro hijo
        ## Checklist
        - [ ] suelta
        MD;

        $tasks = $this->parser->parse('a.md', $markdown)->tasks;

        $this->assertSame([0, 1, 2, 1, 0], array_map(fn ($t) => $t->depth, $tasks));
        $this->assertSame(['Ejercicios', 'Ejercicios', 'Ejercicios', 'Ejercicios', 'Checklist'], array_map(fn ($t) => $t->section, $tasks));
        $this->assertSame([3, 4, 5, 6, 8], array_map(fn ($t) => $t->line, $tasks));
    }

    public function test_ignores_checkboxes_inside_code_fences(): void
    {
        $markdown = "- [ ] real\n```md\n- [ ] ejemplo\n```\n~~~\n- [x] otro ejemplo\n~~~\n- [x] real 2";

        $this->assertCount(2, $this->parser->parse('a.md', $markdown)->tasks);
    }

    public function test_key_ignores_link_targets_and_formatting(): void
    {
        $a = $this->parser->parse('a.md', '- [ ] Lee **[MDN](https://a.example)** y `curl`')->tasks[0];
        $b = $this->parser->parse('a.md', '- [x] lee [MDN](https://b.example)  y curl')->tasks[0];

        $this->assertSame($a->key, $b->key);
    }

    public function test_repeated_texts_get_distinct_keys(): void
    {
        $tasks = $this->parser->parse('a.md', "- [ ] Tests\n- [ ] Tests")->tasks;

        $this->assertNotSame($tasks[0]->key, $tasks[1]->key);
    }

    public function test_windows_line_endings(): void
    {
        $this->assertCount(2, $this->parser->parse('a.md', "- [ ] a\r\n- [x] b\r\n")->tasks);
    }
}

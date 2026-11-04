<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Exception\SourceUnavailable;
use App\Infrastructure\GitHub\GitHubSourceHost;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GitHubSourceHostTest extends TestCase
{
    private function host(string $token = 't0ken'): GitHubSourceHost
    {
        return new GitHubSourceHost($this->app->make(Factory::class), $token, 'https://api.github.test');
    }

    public function test_markdown_files_come_from_recursive_tree(): void
    {
        Http::fake(['api.github.test/repos/o/r/git/trees/*' => Http::response(['tree' => [
            ['path' => 'docs/a.md', 'type' => 'blob', 'sha' => 's1'],
            ['path' => 'docs', 'type' => 'tree', 'sha' => 's2'],
            ['path' => 'src/x.php', 'type' => 'blob', 'sha' => 's3'],
            ['path' => 'NOTES.markdown', 'type' => 'blob', 'sha' => 's4'],
        ]])]);

        $files = $this->host()->markdownFiles('o', 'r', 'feature/x');

        $this->assertSame(['docs/a.md', 'NOTES.markdown'], array_map(fn ($f) => $f->path, $files));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'git/trees/feature%2Fx?recursive=1')
            && $r->hasHeader('Authorization', 'Bearer t0ken'));
    }

    public function test_follows_pagination_links(): void
    {
        Http::fake([
            'api.github.test/repos/o/r/pulls?*page=2*' => Http::response([
                ['number' => 2, 'title' => 'B', 'html_url' => 'u2', 'draft' => true, 'head' => ['sha' => 'h2']],
            ]),
            'api.github.test/repos/o/r/pulls*' => Http::response(
                [['number' => 1, 'title' => 'A', 'html_url' => 'u1', 'draft' => false, 'head' => ['sha' => 'h1']]],
                200,
                ['Link' => '<https://api.github.test/repos/o/r/pulls?state=open&base=main&per_page=100&page=2>; rel="next"'],
            ),
        ]);

        $prs = $this->host()->openPullRequests('o', 'r', 'main');

        $this->assertSame([1, 2], array_map(fn ($p) => $p->number, $prs));
        $this->assertTrue($prs[1]->draft);
    }

    public function test_blob_is_base64_decoded(): void
    {
        Http::fake(['*' => Http::response(['encoding' => 'base64', 'content' => chunk_split(base64_encode("- [x] á\n"), 8, "\n")])]);

        $this->assertSame("- [x] á\n", $this->host()->blob('o', 'r', 'abc'));
    }

    public function test_removed_files_in_pr_are_skipped(): void
    {
        Http::fake(['*' => Http::response([
            ['filename' => 'a.md', 'sha' => 's1', 'status' => 'modified'],
            ['filename' => 'b.md', 'sha' => null, 'status' => 'removed'],
        ])]);

        $this->assertSame(['a.md'], array_map(fn ($f) => $f->path, $this->host()->pullRequestFiles('o', 'r', 1)));
    }

    public function test_not_found_means_not_accessible(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Not Found'], 404)]);

        $this->expectException(RepositoryNotAccessible::class);
        $this->host()->repository('o', 'privado');
    }

    public function test_bad_token_and_missing_token(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Bad credentials'], 401)]);

        try {
            $this->host()->repository('o', 'r');
            $this->fail();
        } catch (SourceUnavailable $e) {
            $this->assertSame('token_rejected', $e->reason->key);
        }

        $this->expectException(SourceUnavailable::class);
        $this->host('')->repository('o', 'r');
    }

    public function test_rate_limit(): void
    {
        Http::fake(['*' => Http::response(['message' => 'API rate limit exceeded'], 403, ['X-RateLimit-Remaining' => '0'])]);

        $this->expectExceptionMessage('rate_limited');
        $this->host()->repository('o', 'r');
    }
}

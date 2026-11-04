<?php

declare(strict_types=1);

namespace App\Infrastructure\GitHub;

use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Exception\SourceUnavailable;
use App\Domain\Model\FileRef;
use App\Domain\Model\PullRequest;
use App\Domain\Model\RepositoryInfo;
use App\Domain\Port\SourceHost;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Adaptador de SourceHost sobre la API REST de GitHub.
 * Doc: https://docs.github.com/en/rest
 */
final class GitHubSourceHost implements SourceHost
{
    /** GitHub corta la lista de ficheros de una PR en 3000 (30 páginas de 100). */
    private const MAX_PAGES = 30;

    public function __construct(
        private readonly Http $http,
        private readonly string $token,
        private readonly string $baseUrl = 'https://api.github.com',
    ) {
    }

    public function repository(string $owner, string $name): RepositoryInfo
    {
        return $this->toRepositoryInfo($this->get("/repos/$owner/$name", "el repositorio $owner/$name")->json());
    }

    public function accessibleRepositories(): array
    {
        $items = $this->getAllPages('/user/repos', [
            'affiliation' => 'owner,collaborator,organization_member',
            'sort' => 'full_name',
        ], 'tus repositorios');

        return array_map($this->toRepositoryInfo(...), $items);
    }

    public function markdownFiles(string $owner, string $name, string $branch): array
    {
        $tree = $this->get(
            "/repos/$owner/$name/git/trees/" . rawurlencode($branch),
            "la rama $branch de $owner/$name",
            ['recursive' => '1'],
        )->json();

        $files = [];
        foreach ($tree['tree'] ?? [] as $entry) {
            if (($entry['type'] ?? null) === 'blob' && preg_match('/\.(md|markdown)$/i', $entry['path'])) {
                $files[] = new FileRef($entry['path'], $entry['sha']);
            }
        }

        return $files;
    }

    public function blob(string $owner, string $name, string $sha): string
    {
        $blob = $this->get("/repos/$owner/$name/git/blobs/$sha", "el fichero $sha de $owner/$name")->json();
        if (($blob['encoding'] ?? '') !== 'base64') {
            return (string) ($blob['content'] ?? '');
        }

        return (string) base64_decode(str_replace("\n", '', (string) $blob['content']), true);
    }

    public function openPullRequests(string $owner, string $name, string $base): array
    {
        $items = $this->getAllPages(
            "/repos/$owner/$name/pulls",
            ['state' => 'open', 'base' => $base],
            "las PRs de $owner/$name",
        );

        return array_map(static fn (array $pr) => new PullRequest(
            number: (int) $pr['number'],
            title: (string) $pr['title'],
            url: (string) $pr['html_url'],
            draft: (bool) ($pr['draft'] ?? false),
            headSha: (string) $pr['head']['sha'],
        ), $items);
    }

    public function pullRequestFiles(string $owner, string $name, int $number): array
    {
        $items = $this->getAllPages("/repos/$owner/$name/pulls/$number/files", [], "la PR #$number de $owner/$name");

        $files = [];
        foreach ($items as $file) {
            // Un fichero borrado en la PR no tiene versión que leer.
            if (($file['status'] ?? '') !== 'removed' && !empty($file['sha'])) {
                $files[] = new FileRef($file['filename'], $file['sha']);
            }
        }

        return $files;
    }

    /** @param array<string, mixed> $repo */
    private function toRepositoryInfo(array $repo): RepositoryInfo
    {
        return new RepositoryInfo(
            owner: (string) $repo['owner']['login'],
            name: (string) $repo['name'],
            defaultBranch: (string) $repo['default_branch'],
            private: (bool) $repo['private'],
            htmlUrl: (string) $repo['html_url'],
            description: $repo['description'] ?? null,
        );
    }

    /**
     * Sigue la cabecera `Link: <…>; rel="next"` hasta la última página.
     *
     * @param array<string, string> $query
     * @return list<array<string, mixed>>
     */
    private function getAllPages(string $path, array $query, string $what): array
    {
        $items = [];
        $url = $this->baseUrl . $path;
        $query += ['per_page' => '100'];

        for ($page = 0; $url !== null && $page < self::MAX_PAGES; $page++) {
            $response = $this->send($url, $query, $what);
            array_push($items, ...$response->json());
            $url = self::nextPage($response->header('Link'));
            // La URL de "next" ya lleva todos los parámetros.
            $query = [];
        }

        return $items;
    }

    /** @param array<string, string> $query */
    private function get(string $path, string $what, array $query = []): Response
    {
        return $this->send($this->baseUrl . $path, $query, $what);
    }

    /** @param array<string, string> $query */
    private function send(string $url, array $query, string $what): Response
    {
        if ($this->token === '') {
            throw new SourceUnavailable('Falta GITHUB_TOKEN: configúralo en el .env del servidor.');
        }

        try {
            // Sin query explícita: pasar [] haría que Guzzle borrase la que ya
            // trae la URL (la de "next" en la paginación).
            $response = $query === []
                ? $this->client()->get($url)
                : $this->client()->get($url, $query);
        } catch (ConnectionException $e) {
            throw new SourceUnavailable('No se puede conectar con GitHub: ' . $e->getMessage(), 0, $e);
        }

        if ($response->successful()) {
            return $response;
        }

        $message = (string) ($response->json('message') ?? $response->reason());
        throw match (true) {
            $response->status() === 404 => new RepositoryNotAccessible($what),
            // GitHub responde 422 cuando la rama del árbol no existe.
            $response->status() === 422 => new RepositoryNotAccessible($what),
            $response->status() === 401 => new SourceUnavailable('GitHub rechaza el token (401). Revisa GITHUB_TOKEN.'),
            in_array($response->status(), [403, 429], true) && $response->header('X-RateLimit-Remaining') === '0'
                => new SourceUnavailable('Límite de peticiones de GitHub agotado; se reintentará en la próxima sincronización.'),
            default => new SourceUnavailable("GitHub ha respondido {$response->status()} al leer $what: $message"),
        };
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'jolo-PM',
            ])
            ->connectTimeout(5)
            ->timeout(20);
    }

    private static function nextPage(string $link): ?string
    {
        return preg_match('/<([^>]+)>;\s*rel="next"/', $link, $m) ? $m[1] : null;
    }
}

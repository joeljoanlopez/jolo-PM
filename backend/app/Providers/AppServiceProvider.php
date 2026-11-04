<?php

namespace App\Providers;

use App\Domain\Port\BoardStore;
use App\Domain\Port\ContentCache;
use App\Domain\Port\ProjectRepository;
use App\Domain\Port\SourceHost;
use App\Infrastructure\Cache\CachingSourceHost;
use App\Infrastructure\GitHub\GitHubSourceHost;
use App\Infrastructure\Persistence\EloquentBoardStore;
use App\Infrastructure\Persistence\EloquentContentCache;
use App\Infrastructure\Persistence\EloquentProjectRepository;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\ServiceProvider;

/**
 * Raíz de composición: el único sitio que conecta cada puerto del dominio
 * (interfaz en App\Domain\Port) con su adaptador (App\Infrastructure).
 *
 * Para cambiar una pieza —GitLab en vez de GitHub, MySQL en vez de SQLite,
 * Redis para la caché— se escribe otro adaptador que implemente el mismo puerto
 * y se cambia su línea aquí. Domain y Application no se tocan.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProjectRepository::class, EloquentProjectRepository::class);
        $this->app->singleton(BoardStore::class, EloquentBoardStore::class);
        $this->app->singleton(ContentCache::class, EloquentContentCache::class);

        $this->app->singleton(SourceHost::class, fn ($app) => new CachingSourceHost(
            new GitHubSourceHost(
                $app->make(Http::class),
                (string) config('jolopm.github.token'),
                (string) config('jolopm.github.api_url'),
            ),
            $app->make(ContentCache::class),
        ));
    }
}

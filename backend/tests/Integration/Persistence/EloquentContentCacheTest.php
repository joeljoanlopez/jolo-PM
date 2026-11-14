<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Infrastructure\Persistence\EloquentContentCache;
use App\Infrastructure\Persistence\Models\CachedBlob;
use Tests\Integration\IntegrationTestCase;

final class EloquentContentCacheTest extends IntegrationTestCase
{
    private EloquentContentCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new EloquentContentCache();
    }

    public function test_misses_return_null_and_hits_return_the_content(): void
    {
        $this->assertNull($this->cache->get('abc'));

        $this->cache->put('abc', "- [x] á\n");

        $this->assertSame("- [x] á\n", $this->cache->get('abc'));
    }

    public function test_putting_the_same_sha_twice_keeps_one_entry(): void
    {
        $this->cache->put('abc', 'uno');
        $this->cache->put('abc', 'uno');

        $this->assertSame(1, CachedBlob::count());
    }

    public function test_prune_deletes_only_what_has_not_been_used_in_time(): void
    {
        $this->cache->put('viejo', 'a');
        $this->cache->put('usado', 'b');

        $this->travel(20)->days();
        $this->cache->get('usado');
        $this->cache->put('nuevo', 'c');

        $this->travel(15)->days();
        $this->assertSame(1, $this->cache->prune(30));

        $this->assertNull($this->cache->get('viejo'));
        $this->assertSame('b', $this->cache->get('usado'));
        $this->assertSame('c', $this->cache->get('nuevo'));
    }
}

<?php

namespace Itjonction\Blockcache\Blade;

use Illuminate\Contracts\Cache\Repository;
use Itjonction\Blockcache\Contracts\Cacheable;
use Itjonction\Blockcache\Contracts\ManagesCaches;

class CacheManager implements ManagesCaches
{
    public Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    // In CacheManager.php

    public function remember($key, $fragment, int | null $ttl = null, string | array $tags = 'views'): string
    {
        $key = $this->normalizeCacheKey($key);
        if ($ttl) {
            $this->cache->tags($tags)->remember($key, $ttl, function () use ($fragment) {
                return $fragment;
            });
        }
        return $this->cache->tags($tags)->rememberForever($key, function () use ($fragment) {
            return $fragment;
        });
    }

    public function has($key, $tags = 'views'): bool
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache->tags($tags)->has($key);
    }

    protected function normalizeCacheKey($key)
    {
        if($key instanceof Cacheable) {
            return $key->getCacheKey();
        }
        return $key;
    }
}

<?php

namespace Itjonction\Blockcache\Blade;

use Illuminate\Contracts\Cache\Repository;
use Itjonction\Blockcache\Contracts\Cacheable;
use Itjonction\Blockcache\Contracts\ManagesCaches;

class CacheManager implements ManagesCaches
{
    protected Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    // In CacheManager.php

    public function put($key, $fragment, $ttl = null): string
    {
        $key = $this->normalizeCacheKey($key);
        if ($ttl) {
            $this->cache->tags('views')->put($key, $fragment, $ttl);
            return $fragment;
        }
        return $this->cache->tags('views')->rememberForever($key, function () use ($fragment) {
            return $fragment;
        });
    }

    public function has($key): bool
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache->tags('views')->has($key);
    }

    protected function normalizeCacheKey($key)
    {
        if($key instanceof Cacheable) {
            return $key->getCacheKey();
        }
        return $key;
    }
}

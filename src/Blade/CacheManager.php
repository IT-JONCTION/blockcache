<?php

namespace Itjonction\Blockcache\Blade;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;

class CacheManager
{

    protected $cache;

    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    public function put($key, $fragment)
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache
          ->tags('views')
          ->rememberForever($key, function () use ($fragment) {
            return $fragment;
        });
    }

    public function has($key)
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache->tags('views')->has($key);
    }

    protected function normalizeCacheKey($key)
    {
        if($key instanceof Model) {
            return $key->getCacheKey();
        }
        return $key;
    }
}

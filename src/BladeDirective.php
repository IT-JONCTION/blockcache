<?php

namespace Itjonction\Blockcache;

class BladeDirective
{
    protected array $keys = [];
    protected CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }
    public function setUp($model)
    {
        ob_start();
        $this->keys[] = $key = $model->getCacheKey();
        return $this->cache->has($key);
    }

    public function tearDown()
    {
        return $this->cache->put(
          array_pop($this->keys), ob_get_clean()
        );
    }

}

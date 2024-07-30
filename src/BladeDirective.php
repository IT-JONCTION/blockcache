<?php

namespace Itjonction\Blockcache;

use Exception;
use Itjonction\Blockcache\Blade\CacheManagerInterface;

class BladeDirective
{
    protected array $keys = [];
    protected CacheManagerInterface $cache;

    public function __construct(CacheManagerInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws Exception
     */
    public function setUp($keyOrModel, $key = null)
    {
        ob_start();
        $this->keys[] = $key = $this->normalizeKey($keyOrModel, $key);
        return $this->cache->has($key);
    }

    public function tearDown()
    {
        return $this->cache->put(
          array_pop($this->keys), ob_get_clean()
        );
    }

    /**
     * Normalize the cache key.
     *
     * @param  mixed  $item
     * @param  string|null  $key
     * @return mixed|string|null
     * @throws Exception
     */
    protected function normalizeKey(mixed $item, string $key = null): mixed
    {
        // If the user wants to provide their own cache
        // key, we'll opt for that.
        if (is_string($item) || is_string($key)) {
            return is_string($item) ? $item : $key;
        }

        // Otherwise we'll try to use the item to calculate
        // the cache key, itself.
        if (is_object($item) && method_exists($item, 'getCacheKey')) {
            return $item->getCacheKey();
        }

        // If we're dealing with a collection, we'll
        // use a hashed version of its contents.
        if ($item instanceof \Illuminate\Support\Collection) {
            return md5($item);
        }

        throw new Exception('Could not determine an appropriate cache key.');
    }

}

<?php

namespace Itjonction\Blockcache;

use Exception;
use Illuminate\Support\Collection;
use Itjonction\Blockcache\Contracts\ManagesCaches;
use Illuminate\Support\Facades\Log;

class BladeDirective
{
    protected array $keys = [];
    protected array $options = [];
    protected ManagesCaches $cache;

    public function __construct(ManagesCaches $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws Exception
     */
    public function setUp($keyOrModel, array $options = [])
    {
        ob_start();
        $this->options = $options;
        try {
            $this->keys[] = $key = $this->normalizeKey($keyOrModel);
            return $this->cache->has($key);
        } catch (Exception $e) {
            // don't allow exceptions to bubble up as they will break the view
            Log::error($e->getMessage());
            return false;
        }
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
     * @return mixed|string|null
     * @throws Exception
     */
    protected function normalizeKey(mixed $item): mixed
    {
        // User provided a string - so that is the key.
        if (is_string($item) ) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'getCacheKey')) {
            return $item->getCacheKey();
        }

        // If a collection, the key is a hash of its contents.
        if ($item instanceof Collection) {
            return md5($item);
        }

        throw new Exception('Could not determine an appropriate cache key.');
    }

    public function getOptions()
    {
        return $this->options;
    }
}

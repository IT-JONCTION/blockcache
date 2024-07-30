<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Itjonction\Blockcache\Blade\CacheManager;

class BladeCacheManagerTest extends TestCase
{

    function test_it_caches_the_given_key_from_model()
    {
        $post = $this->makePost();
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->put($post, '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has($post));
    }
    function test_it_caches_the_given_key_from_key()
    {
        $post = $this->makePost();
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->put($post->getCacheKey(), '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has($post->getCacheKey()));
    }
    function test_it_caches_the_given_key_from_string()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->put('arbitrary-string', '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has('arbitrary-string'));
    }
}

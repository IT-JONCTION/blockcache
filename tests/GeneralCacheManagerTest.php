<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Itjonction\Blockcache\General\CacheManager;

class GeneralCacheManagerTest extends TestCase
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

    function test_it_can_use_a_block_cache()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->startCache('arbitrary-string');
        echo "<div>view fragment</div>";
        $output = $cacheManager->endCache();
        $this->assertTrue($cacheManager->has('arbitrary-string'));
        $this->assertEquals('<div>view fragment</div>', $output);
    }
}

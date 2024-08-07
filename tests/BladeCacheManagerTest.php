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

        $cacheManager->remember($post, '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has($post));
    }
    function test_it_caches_the_given_key_from_key()
    {
        $post = $this->makePost();
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->remember($post->getCacheKey(), '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has($post->getCacheKey()));
    }
    function test_it_caches_the_given_key_from_string()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $cacheManager->remember('arbitrary-string', '<div>view fragment</div>');
        $this->assertTrue($cacheManager->has('arbitrary-string'));
    }

    public function test_it_remembers_fragment_with_ttl()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $key = 'test-key';
        $fragment = '<div>view fragment with ttl</div>';
        $ttl = 60; // 1 minute

        // Call remember method to cache the fragment with a TTL
        $cachedFragment = $cacheManager->remember($key, $fragment, $ttl);

        // Check that the fragment is returned correctly
        $this->assertEquals($fragment, $cachedFragment, 'The returned fragment should match the cached fragment.');

        // Retrieve the cached fragment from the cache store
        $retrievedFragment = $cacheManager->get($key);

        // Check that the cached fragment is the same as the original fragment
        $this->assertEquals($fragment, $retrievedFragment, 'The fragment stored in the cache should match the original fragment.');
    }

    public function test_it_remembers_fragment_forever_when_no_ttl()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $key = 'test-key-forever';
        $fragment = '<div>view fragment forever</div>';

        // Call remember method to cache the fragment forever
        $cachedFragment = $cacheManager->remember($key, $fragment);

        // Check that the fragment is returned correctly
        $this->assertEquals($fragment, $cachedFragment, 'The returned fragment should match the cached fragment.');

        // Retrieve the cached fragment from the cache store
        $retrievedFragment = $cacheManager->get($key);

        // Check that the cached fragment is the same as the original fragment
        $this->assertEquals($fragment, $retrievedFragment, 'The fragment stored in the cache should match the original fragment.');
    }
}

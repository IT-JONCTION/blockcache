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

    /**
     * @throws Exception
     */
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
        $this->assertEquals($fragment, $retrievedFragment, 'The retrieved fragment should match the cached fragment.');
    }

    public function test_it_remembers_fragment_forever_when_no_ttl()
    {
        $cache = new Repository(
          new ArrayStore
        );
        $cacheManager = new CacheManager($cache);

        $key = 'test-key';
        $fragment = '<div>view fragment forever</div>';

        // Call remember method to cache the fragment forever
        $cachedFragment = $cacheManager->remember($key, $fragment);

        // Check that the fragment is returned correctly
        $this->assertEquals($fragment, $cachedFragment, 'The returned fragment should match the cached fragment.');

        // Retrieve the cached fragment from the cache store
        $retrievedFragment = $cacheManager->get($key);
        $this->assertEquals($fragment, $retrievedFragment, 'The retrieved fragment should match the cached fragment.');
    }
}

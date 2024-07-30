<?php


use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Itjonction\Blockcache\BladeDirective;
use Itjonction\Blockcache\Blade\CacheManager;

class BladeDirectiveTest extends TestCase
{

    protected CacheManager $cacheManager;
    public function test_it_sets_up_opening_cache_directive()
    {
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $isCached = $directive->setUp($post);
        $this->assertFalse($isCached);
        echo '<div>view fragment</div>';
        $cachedFragment = $directive->tearDown();
        $this->assertEquals('<div>view fragment</div>', $cachedFragment);
        $this->assertTrue($this->cacheManager->has($post));
    }

    protected function createNewCacheDirective(): BladeDirective
    {
        $cache = new Repository(
            new ArrayStore
        );
        $this->cacheManager = new CacheManager($cache);
        return new BladeDirective($this->cacheManager);
    }
}

<?php


use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Itjonction\Blockcache\BladeDirective;
use Itjonction\Blockcache\Blade\CacheManager;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;
use TiMacDonald\Log\ChannelFake;
use Illuminate\Support\Facades\Log;

class BladeDirectiveTest extends TestCase
{

    protected CacheManager $cacheManager;

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function test_it_can_read_an_array_as_second_param(): void
    {
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $directive->setUp($post, ['key' => 'value']);
        $this->assertTrue(is_array($directive->getOptions()));
        $this->assertEquals('value', $directive->getOptions()['key']);
        $directive->tearDown();
    }

    public function test_it_knows_when_we_ask_for_ttl()
    {
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $directive->setUp($post, ['ttl' => 60]);
        $options = $directive->getOptions();
        $this->assertIsArray($options, 'Options should be an array.');
        $this->assertArrayHasKey('ttl', $options, 'Options should contain a ttl key.');
        $this->assertEquals(60, $options['ttl'], 'TTL value should be 60.');
        $directive->tearDown();
    }

    public function test_it_throws_error_when_unknown_strategy_is_asked_for()
    {
        LogFake::Bind();
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $directive->setUp($post, ['GuineaPigs' => true]);
        $directive->tearDown();
        Log::channel('stack')->assertLogged(fn (LogEntry $log) =>
          $log->level === 'error'
          && $log->message === 'Unknown strategy: GuineaPigs'
        );
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

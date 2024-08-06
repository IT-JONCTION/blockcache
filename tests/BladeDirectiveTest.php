<?php


use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Itjonction\Blockcache\BladeDirective;
use Itjonction\Blockcache\Blade\CacheManager;
use Psr\Log\LoggerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class BladeDirectiveTest extends TestCase
{

    protected CacheManager $cacheManager;
    protected LoggerInterface $logger;
    protected TestHandler $testHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->testHandler = new TestHandler();
        $this->logger = new Logger('blockcache_test');
        $this->logger->pushHandler($this->testHandler);
    }

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
        $directive->tearDown();
        $this->assertTrue(is_array($directive->getOptions()));
        $this->assertEquals('value', $directive->getOptions()['key']);
    }

    public function test_it_handles_single_ttl_value()
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

    public function test_it_handles_multiple_ttl_value()
    {
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $directive->setUp($post, ['ttl' => [60, 120]]);
        $directive->tearDown();
        $options = $directive->getOptions();
        $this->assertIsArray($options, 'Options should be an array.');
        $this->assertArrayHasKey('ttl', $options, 'Options should contain a ttl key.');
        $this->assertIsInt($directive->getTtl(), 'TTL value should be a random Int.');
    }

    public function test_it_throws_error_when_unknown_strategy_is_asked_for()
    {
        $directive = $this->createNewCacheDirective();
        $post = $this->makePost();
        $directive->setUp($post, ['GuineaPigs' => true]);
        $directive->tearDown();
        $records = $this->testHandler->getRecords();
        $this->assertTrue($this->testHandler->hasErrorThatContains('Unknown strategy: GuineaPigs'));
    }

    public function test_it_handles_a_single_tag()
    {
        $directive = $this->createNewCacheDirective();
        $directive->setUp('my-unique-key', ['tags' => 'tag']);
        echo "<div>view tag</div>";
        $directive->tearDown();
        $options = $directive->getOptions();
        $this->assertIsArray($options, 'Options should be an array.');
        $this->assertArrayHasKey('tags', $options, 'Options should contain a tags key.');
        $this->assertIsString($options['tags'], 'Tag should be a string.');
        //test that we set the tag
        $this->assertTrue($this->cacheManager->has('my-unique-key','tag'));
    }

    public function test_it_handles_versions()
    {
        $directive = $this->createNewCacheDirective();
        $directive->setUp('my-unique-key', ['version' => '1.4.3']);
        echo "<div>view tag</div>";
        $directive->tearDown();
        $options = $directive->getOptions();
        $this->assertIsArray($options, 'Options should be an array.');
        $this->assertArrayHasKey('version', $options, 'Options should contain a tags key.');
        $this->assertIsString($options['version'], 'Tag should be a string.');
        //test that we set the tag
        $this->assertTrue($this->cacheManager->has('my-unique-key/v1.4.3'));
    }

    public function test_it_handles_multiple_tags()
    {
        $directive = $this->createNewCacheDirective();
        $directive->setUp('my-unique-key', ['tags' => ['tag1','tag2']]);
        echo "<div>view tags</div>";
        $directive->tearDown();
        $options = $directive->getOptions();
        $this->assertIsArray($options, 'Options should be an array.');
        $this->assertArrayHasKey('tags', $options, 'Options should contain a tags key.');
        $this->assertIsArray($options['tags'], 'Tags should be a Array.');
        $this->assertTrue($this->cacheManager->has('my-unique-key',['tag1','tag2']));
    }

// No-op for legacy
    protected function createNewCacheDirective(): BladeDirective
    {
        $cache = new Repository(
            new ArrayStore
        );
        $this->cacheManager = new CacheManager($cache);
        return new BladeDirective($this->cacheManager, $this->logger);
    }
}

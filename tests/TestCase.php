<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Facade;
use Itjonction\Blockcache\Contracts\Cacheable;
use Itjonction\Blockcache\HasCacheKey;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

abstract class TestCase extends PHPUnit\Framework\TestCase
{
    protected LoggerInterface $logger;
    protected TestHandler $testHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Container::setInstance(new Container());

        $app->singleton('config', fn () => new Repository(['logging' => ['default' => 'stack']]));
        /** @phpstan-ignore argument.type */
        $app->singleton('log', fn () => new LogManager($app));

        /** @phpstan-ignore argument.type */
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $this->setUpDatabase();
        $this->migrateTables();

        $this->testHandler = new TestHandler();
        $this->logger = new Logger('blockcache_test');
        $this->logger->pushHandler($this->testHandler);
    }

    protected function setUpDatabase(): void
    {
        $database = new DB;
        $database->addConnection([
          'driver' => 'sqlite',
          'database' => ':memory:',
        ]);
        $database->bootEloquent();
        $database->setAsGlobal();
    }

    protected function migrateTables(): void
    {
        DB::schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });
    }

    protected function makePost(): Post
    {
        $post = new Post;
        $post->title = 'My first post';
        $post->save();
        return $post;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}

class Post extends Model implements Cacheable
{
    use HasCacheKey;

    public mixed $updated_at;
}

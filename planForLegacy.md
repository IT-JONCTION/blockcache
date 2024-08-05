Implementing `stale-while-revalidate` in your cache manager involves serving stale content while asynchronously refreshing the cache. Here's a step-by-step approach to achieve this in your PHP application.

### Steps to Implement `stale-while-revalidate`

1. **Modify the Cache Manager**: Adjust your cache manager to support `stale-while-revalidate`.
2. **Use Background Processing**: Use a queue system like Laravel Queues to handle the background cache refresh.

### Step 1: Modify the Cache Manager

First, update your cache manager to handle `stale-while-revalidate` logic.

```php
namespace Itjonction\Blockcache\General;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Queue;

class CacheManager
{
    protected $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function put($key, $fragment)
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache
            ->tags('views')
            ->forever($key, $fragment);
    }

    public function has($key)
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache->tags('views')->has($key);
    }

    public function get($key)
    {
        $key = $this->normalizeCacheKey($key);
        return $this->cache->tags('views')->get($key);
    }

    public function staleWhileRevalidate($key, $callback)
    {
        if ($this->has($key)) {
            // Return the stale content immediately
            $content = $this->get($key);

            // Dispatch a job to refresh the cache
            Queue::push(new RefreshCacheJob($key, $callback));

            return $content;
        }

        // Cache miss, generate and cache the content
        $content = $callback();
        $this->put($key, $content);

        return $content;
    }

    protected function normalizeCacheKey($key)
    {
        if ($key instanceof Model) {
            return $key->getCacheKey();
        }
        return $key;
    }
}
```

### Step 2: Create the Job for Cache Refresh

Create a job class that will handle the background cache refresh.

```php
namespace Itjonction\Blockcache\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Itjonction\Blockcache\General\CacheManager;

class RefreshCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $key;
    protected $callback;

    public function __construct($key, $callback)
    {
        $this->key = $key;
        $this->callback = $callback;
    }

    public function handle(CacheManager $cacheManager)
    {
        $content = call_user_func($this->callback);
        $cacheManager->put($this->key, $content);
    }
}
```

### Step 3: Update Your Application Code

Update your application code to use the new `staleWhileRevalidate` method.

```php
use Itjonction\Blockcache\General\CacheManager;

$cacheManager = app(CacheManager::class);

$content = $cacheManager->staleWhileRevalidate('unique-key', function () {
    // Generate the content
    return "<div>Your HTML content here</div>";
});

echo $content;
```

### Step 4: Configure the Queue

Make sure your queue system is configured. You can use different queue drivers supported by Laravel like database, Redis, etc.

1. **Queue Configuration**: Update your `.env` file with the queue driver you want to use, e.g., `database`.

```env
QUEUE_CONNECTION=database
```

2. **Queue Table Migration**: If using the database driver, run the following commands to create the necessary tables.

```sh
php artisan queue:table
php artisan migrate
```

3. **Running the Queue Worker**: Start the queue worker to process jobs.

```sh
php artisan queue:work
```

### Conclusion

By following these steps, you implement `stale-while-revalidate` caching in your Laravel application. The content is served immediately while a background job refreshes the cache asynchronously. This approach ensures minimal latency for end-users while keeping the cached content up-to-date.

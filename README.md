# Blockcache

Blockcache is a package for Laravel that provides nested block caching for your view logic.

## Laravel Installation

### Step 2: Service Provider

For your Laravel app, open `config/app.php` and, within the `providers` array, append:

```php
Itjonction\Blockcache\BlockcacheServiceProvider::class
```

This will bootstrap the package into Laravel.

### Step 3: Cache Driver

For this package to function properly, you must use a Laravel cache driver that supports tagging (like `Cache::tags('foo')`). Drivers such as Memcached and Redis support this feature.

Check your `.env` file, and ensure that your `CACHE_DRIVER` choice accommodates this requirement:

```dotenv
CACHE_DRIVER=memcached
```

> Refer to [Laravel's cache configuration documentation](https://laravel.com/docs/5.2/cache#configuration) if you need any help.

## Usage

### The Basics

With the package now installed, you may use the provided `@cache` Blade directive anywhere in your views, like so:

```html
@cache('my-cache-key')
    <div>
        <h1>Hello World</h1>
    </div>
@endcache
```

By surrounding this block of HTML with the `@cache` and `@endcache` directives, you are instructing the package to cache the given HTML. While this example is trivial, you can imagine more complex views with nested caches and lazy-loaded relationship calls triggering additional database queries. After the initial page load that caches the HTML fragment, each subsequent refresh will pull from the cache, preventing additional database queries.

In production, this will cache the HTML fragment indefinitely. For local development, the relevant cache will automatically flush each time you refresh the page, allowing you to update your views and templates without needing to clear the cache manually.

## Legacy Templates and Classes

While this package relies on Laravel classes, Laravel doesn't need to be bootstrapped. To use this library in a non-Laravel template, do the following to use `Blockcache` directly:

```php
use Itjonction\Blockcache\General\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Redis\RedisManager;
use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Application;

// Configure Redis connection settings
$config = [
    'default' => [
        'url' => env('REDIS_URL', null),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
];

// Create the Redis manager instance
$redisManager = new RedisManager($app, 'predis', ['default' => $config['default']]);

// Create the Redis store instance
$redisStore = new RedisStore($redisManager, 'cache');

// Create the Cache repository instance
$cache = new Repository($redisStore);
$cacheManager = new CacheManager($cache);
$cacheManager->startCache('my-cache-key');
echo "<div>view fragment</div>";
$output = $cacheManager->endCache();
```

Alternatively, even in legacy code, you can still bootstrap the Laravel application instance:

```php
// bootstrap_laravel.php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;

// Path to the Laravel application
$appPath = __DIR__ . '/path/to/your/laravel';

// Require the Laravel application
$app = require $appPath . '/bootstrap/app.php';

// Make the kernel (HTTP kernel is enough to access most services)
$kernel = $app->make(Kernel::class);

// Bootstrap the application
$kernel->bootstrap();

// You can now use Laravel's config, cache, etc.
return $app;
```

This allows you to cache any view fragment, regardless of whether it's a Blade template or not.

```php
use Itjonction\Blockcache\General\CacheManager;
use Illuminate\Foundation\Application;

// Create a new application instance (optional but useful for dependency resolution)
$app = require __DIR__ . '/path/to/bootstrap_laravel.php';

// Create the Cache repository instance
$cacheManager = new CacheManager($app->make('cache'));
$cacheManager->startCache('my-cache-key');
echo "<div>view fragment</div>";
$output = $cacheManager->endCache();
```

Since your production server will cache the fragments indefinitely, add a step to your deployment process to clear the relevant cache:

```php
Cache::tags('views')->flush();
```

### Caching Models

While you're free to hard-code any string for the cache key, the true power of Russian-Doll caching comes into play when using a cache invalidation strategy, such as a timestamp-based approach.

Consider the following fragment:

```html
@cache($post)
    <article>
        <h2>{{ $post->title }}</h2>
        <p>Written By: {{ $post->author->username }}</p>

        <div class="body">{{ $post->body }}</div>
    </article>
@endcache
```

In this example, we're passing the `$post` object to the `@cache` directive instead of a string. The package will look for a `getCacheKey()` method on the model. To enable this, have your Eloquent model use the `Itjonction\Blockcache\HasCacheKey` trait:

```php
use Itjonction\Blockcache\HasCacheKey;

class Post extends Eloquent
{
    use HasCacheKey;
}
```

Alternatively, you may use this trait on a parent class that your Eloquent models extend.

Now, the cache key for this fragment will include the object's `id` and `updated_at` timestamp: `App\Post/1-13241235123`.

> The key is that, because we factor the `updated_at` timestamp into the cache key, whenever you update the post, the cache key will change, effectively busting the cache.

#### Touching

For this technique to work properly, we need a mechanism to alert parent relationships (and subsequently bust parent caches) each time a model is updated. Here's a basic workflow:

1. Model is updated in the database.
2. Its `updated_at` timestamp is refreshed, triggering a new cache key for the instance.
3. The model "touches" (or pings) its parent.
4. The parent's `updated_at` timestamp is updated, busting its associated cache.
5. Only the affected fragments re-render. All other cached items remain untouched.

Laravel offers this "touch" functionality out of the box. Consider a `Note` object that needs to alert its parent `Card` relationship each time an update occurs.

```php
<?php

namespace App;

use Itjonction\Blockcache\HasCacheKey;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasCacheKey;

    protected $touches = ['card'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
```

The `$touches = ['card']` portion instructs Laravel to ping the `card` relationship's timestamps each time the note is updated.

Now, you might render your view like this:

**resources/views/cards/_card.blade.php**

```html
@cache($card)
    <article class="Card">
        <h2>{{ $card->title }}</h2>

        <ul>
            @foreach ($card->notes as $note)
                @include ('cards/_note')
            @endforeach
        </ul>
    </article>
@endcache
```

**resources/views/cards/_note.blade.php**

```html
@cache($note)
    <li>{{ $note->body }}</li>
@endcache
```

Notice the Russian-Doll style cascading for our caches; if any note is updated, its individual cache will clear, along with its parent, but any siblings will remain untouched.

### Other Invalidation Strategies

The `@cache($key)` directive will either retrieve content from the cache or create a new cache entry for the specified content. By manipulating the cache key, you can implement various caching strategies.

The secret to these strategies is using the cache utility classes provided by the `HasCacheKey` trait, which should be added to classes where you want to use the block cache. The trait includes methods for well-known cache invalidation strategies.

You can implement various cache invalidation strategies using a key-value store in the form of an associative array as the second parameter of the Blade directive. Here are the strategies:

#### Write-Through Cache: done

Updates cache key when the data within the cache changes. This strategy relies on the `updated_at` timestamp of the model.

```html
@cache($eloquentModel->getCacheKey())
    <div>view fragment</div>
@endcache
```

#### Manual Invalidation: done

Requires explicit action to clear or refresh the cache. This is the default behavior.

```html
@cache('my-unique-key')
    <div>view fragment</div>
@endcache
```

To manually clear this cache, use the below (views is the default tag):

```php
Cache::tags('views')->flush();
```

#### Time-to-Live (TTL): done

Automatically expires cached content after a period set in seconds.

```html
@cache('my-unique-key', ['ttl' => 60])
    <div>view fragment</div>
@endcache
```
Or you can set the TTL as a random period by setting a range:

```html
@cache('my-unique-key', ['ttl' => [60, 120]])
    <div>view fragment</div>
@endcache
```
When caching various fragments, this will ensure that they don't all expire at the same time.

#### Cache Tags: done

Tags related content together, allowing for group invalidation.

```html
@cache('my-unique-key', ['tags' => ['tag1', 'tag2']])
    <div>view fragment</div>
@endcache
```

### Understanding Cache Tags in Laravel

**Cache Tags**:
- Allow you to assign multiple tags to a cache item.
- Provide a way to group related cache items and perform bulk operations (e.g., invalidate all items with a specific tag).

### How Cache Tags Work

When you use tags, you essentially create a composite key that includes all the specified tags. This means that when you 
store an item with multiple tags, you must also retrieve it with the same set of tags.

### Example

If you store an item with tags `['orders', 'invoices']`, the cache system internally creates a key that represents this 
combination of tags. To retrieve this item, you must specify both tags.

### Storing and Retrieving with Tags

When you store an item with:
```php
$this->cache->tags(['orders', 'invoices'])->put('my-unique-key', $fragment, $ttl);
```
To retrieve it, you must use:
```php
$this->cache->tags(['orders', 'invoices'])->get('my-unique-key');
```

If you try to retrieve it with a single tag or a different combination, it won't find the item.

### Testing Cache Tags

1. **Passing Test**: This passes because you check the existence of the key with the exact combination of tags.
    ```php
    $this->assertTrue($this->cacheManager->has('my-unique-key',['orders','invoices']));
    ```

2. **Failing Test**: This fails because you check the existence with individual tags, which doesn't match the composite key.
    ```php
    $this->assertTrue($this->cacheManager->has('my-unique-key','orders'));
    $this->assertTrue($this->cacheManager->has('my-unique-key','invoices'));
    ```

### Why Is This Happening?

When you use:
```php
$this->cache->tags(['orders', 'invoices'])->put('my-unique-key', $fragment, $ttl);
```
- It stores the item under a composite key generated from `['orders', 'invoices']`.

When you check:
```php
$this->cache->has('my-unique-key', 'orders'); // Incorrect
$this->cache->has('my-unique-key', 'invoices'); // Incorrect
```
- These checks don't find the item because it's stored under the composite key, not under each individual tag.

### Correct Approach for Tests

To correctly test the cache with multiple tags, always use the exact tag combination used during storage:

**Test for Multiple Tags:**
```php
public function test_it_handles_multiple_tags()
{
    $directive = $this->createNewCacheDirective();
    $directive->setUp('my-unique-key', ['tags' => ['orders','invoices']]);
    echo "<div>view tags</div>";
    $directive->tearDown();
    $options = $directive->getOptions();
    $this->assertIsArray($options, 'Options should be an array.');
    $this->assertArrayHasKey('tags', $options, 'Options should contain a tags key.');
    $this->assertIsArray($options['tags'], 'Tags should be an array.');
    // Check using the exact combination of tags
    $this->assertTrue($this->cacheManager->has('my-unique-key', ['orders', 'invoices']));
}
```

### Bulk Operations and Invalidation

#### Invalidating Cache Items with Tags

When you invalidate cache items using tags, it affects all items that include those tags.

**Example**:
If you have an item tagged with `['orders', 'invoices']` and you invalidate `orders`, it will also invalidate the item 
tagged with both `orders` and `invoices`.

**Code Example**:
```php
Cache::tags('orders')->flush();
```
This will invalidate:
- Items tagged with `['orders']`
- Items tagged with `['orders', 'invoices']`
- Any other combination that includes `orders`

**Explanation**:
- **Composite Key**: Understand that tags create a composite key.
- **Consistency**: Use the same tag combination for storing and retrieving.
- **Bulk Operations**: Use tags to manage groups of cache items efficiently.
- **Invalidation**: Invalidating a single tag will affect all items that include that tag, even if they have additional tags.

By understanding and correctly using cache tags, you can efficiently group, manage, and invalidate related cache items. 
Always remember to use the exact combination of tags for storing and retrieving cache items, and be aware that invalidating 
a tag will affect all items that include that tag, even if they have additional tags. 

#### Content Versioning: done

Uses the version numbers to force cache updates on each release.

```html
@cache('my-unique-key', ['version' => 'v1'])
    <div>view fragment</div>
@endcache
```

#### Stale-While-Revalidate: todo

Serves stale content while asynchronously updating the cache.

```html
@cache('my-unique-key', ['stale-while-revalidate' => true])
    <div>view fragment</div>
@endcache
```

#### Conditional Requests: todo (1% done)

Uses HTTP headers to validate cache freshness before serving.

```html
@cache('my-unique-key', ['conditional' => true])
<div>view fragment</div>
@endcache
```
Conditional header-based cache invalidation allows the client to inform the server that the cached data is stale, 
prompting the server to send fresh data.

#### Event-Driven Invalidation: on hold : blocked by lack of event support in legacy code

Triggers cache invalidation based on specific events.

```html
@cache('my-unique-key', ['event' => 'modelUpdated'])
    <div>view fragment</div>
@endcache
```

#### Legacy Invalidation Strategies
All strategies are available for use in your legacy code, even if you're not using Laravel.

```php
$cacheManager->startCache('my-cache-key', ['ttl' => 60]);
```

### Caching Collections

You may also wish to cache a Laravel collection:

```html
@cache($posts)
    @foreach ($posts as $post)
        @include ('post')
    @endforeach
@endcache
```

As long as the `$posts` collection contents do not change, that `@foreach` section will never run. Instead, we'll pull from the cache.

Behind the scenes, this package will detect that you've passed a Laravel collection to the `cache` directive and will generate a unique cache key for the collection.

## FAQ

**1. Is there any way to override the cache key for a model instance?**

Yes. For example:

```html
@cache('my-custom-key')
    <div>view here</div>
@endcache
```
Simply providing a string, rather than a model, instructs the package to use `my-custom-key` for the cache instead.

---

**TODOs:**
1. Link to a video of the POC.
2. Determine how to set a flag to avoid caching in dev or recognize template changes without relying on middleware.
3. Write all the invalidation strategies.
- [x] Write-Through Cache
- [x] Manual Invalidation
- [x] Time-to-Live (TTL)
- [x] Cache Tags
- [x] Content Versioning
- [ ] Stale-While-Revalidate
- [ ] Conditional Requests
- [ ] Event-Driven Invalidation


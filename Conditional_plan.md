To implement conditional cache invalidation based on HTTP headers in your Laravel application, you can follow these steps:

1. **Inspect HTTP Headers**: Check for specific HTTP headers in the incoming request.
2. **Conditional Cache Invalidation**: Invalidate cache entries based on the presence and values of these headers.
3. **Middleware to Handle Logic**: Use middleware to encapsulate this logic, ensuring it runs on each request.

### Step-by-Step Implementation

### Step 1: Create Middleware

Create a middleware that will inspect the HTTP headers and invalidate the cache conditionally.

#### Middleware Command

Run the following Artisan command to create middleware:

```bash
php artisan make:middleware ConditionalCacheInvalidation
```

#### Middleware Code

Edit the generated middleware in `app/Http/Middleware/ConditionalCacheInvalidation.php`:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ConditionalCacheInvalidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Example header check
        if ($request->hasHeader('X-Invalidate-Cache')) {
            $cacheTags = $request->header('X-Invalidate-Cache');
            $tags = explode(',', $cacheTags);

            foreach ($tags as $tag) {
                Cache::tags(trim($tag))->flush();
            }
        }

        return $next($request);
    }
}
```

### Step 2: Register Middleware

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // Other middleware
    \App\Http\Middleware\ConditionalCacheInvalidation::class,
];
```

### Step 3: Use Middleware to Invalidate Cache Based on HTTP Headers

Here’s an example of how you might use HTTP headers to conditionally invalidate cache entries.

### Example Request

You can send a request with a custom header to invalidate cache:

```bash
curl -X GET "http://your-laravel-app.com/some-endpoint" -H "X-Invalidate-Cache: tag1, tag2"
```

### Explanation

1. **Check for Headers**: The middleware checks for the presence of the `X-Invalidate-Cache` header.
2. **Parse Tags**: It splits the header value by commas to get individual cache tags.
3. **Invalidate Cache**: It iterates through each tag and calls `Cache::tags($tag)->flush()` to invalidate all cache entries associated with that tag.

### Step 4: Integrate with Cache Logic in Blade Directives

Ensure your cache logic in Blade directives is compatible with tag-based invalidation.



### Example Usage in Blade

```blade
@cache('my-unique-key', ['tags' => ['tag1', 'tag2'], 'versions' => 1])
    <!-- Cached content here -->
    <div>Cache me if you can!</div>
@endcache
```

### Step 5: Update Middleware to Handle Multiple Invalidation Types
It would make sense for the method to be flexible enough to invalidate cache entries based on keys, tags, versions, 
or any combination of these. This flexibility can be particularly useful for managing complex caching strategies.
Here's how you can extend the middleware to handle invalidation of keys, tags, and versions:

Modify the `ConditionalCacheInvalidation` middleware to check for keys, tags, and versions in the HTTP headers.

#### Middleware Code

Edit `app/Http/Middleware/ConditionalCacheInvalidation.php` to handle multiple types of cache invalidation:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ConditionalCacheInvalidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->hasHeader('X-Invalidate-Cache')) {
            $invalidateCache = json_decode($request->header('X-Invalidate-Cache'), true);

            if (isset($invalidateCache['keys'])) {
                $this->invalidateKeys($invalidateCache['keys']);
            }

            if (isset($invalidateCache['tags'])) {
                $this->invalidateTags($invalidateCache['tags']);
            }

            if (isset($invalidateCache['versions'])) {
                $this->invalidateVersions($invalidateCache['versions']);
            }
        }

        return $next($request);
    }

    protected function invalidateKeys(array $keys)
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    protected function invalidateTags(array $tags)
    {
        foreach ($tags as $tag) {
            Cache::tags($tag)->flush();
        }
    }

    protected function invalidateVersions(array $versions)
    {
        foreach ($versions as $version) {
            Cache::tags('version:' . $version)->flush();
        }
    }
}
```

### Blade Template

```blade
@cache('my-unique-key', ['tags' => ['tag1', 'tag2']], ['versions' => 'version1'])
    <div>Cache me if you can!</div>
@endcache
```

### Step 6: Testing

Test the conditional cache invalidation by sending requests with different header values and verifying that the cache is invalidated as expected.

### Example Request

You can send a request with a custom header to invalidate cache:

```bash
curl -X GET "http://your-laravel-app.com/some-endpoint" -H "X-Invalidate-Cache: {\"keys\": [\"key1\"], \"tags\": [\"tag1\"], \"versions\": [\"version1\"]}"
```

## Legacy Code Support
You can load middleware in a legacy router. In Laravel, middleware can be applied to specific routes, route groups, or globally. 
Even if you are using a legacy router, you can still apply middleware to handle HTTP requests and responses.

Here’s how you can achieve this:

### Step-by-Step Implementation

1. **Create Middleware**: If you haven't already, create the middleware that handles conditional cache invalidation.
2. **Register Middleware**: Register the middleware in the kernel or route middleware.
3. **Apply Middleware to Legacy Routes**: Apply the middleware to your legacy routes.

### Step 1: Create Middleware

Assuming you have already created the `ConditionalCacheInvalidation` middleware:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ConditionalCacheInvalidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->hasHeader('X-Invalidate-Cache')) {
            $invalidateCache = json_decode($request->header('X-Invalidate-Cache'), true);

            if (isset($invalidateCache['keys'])) {
                $this->invalidateKeys($invalidateCache['keys']);
            }

            if (isset($invalidateCache['tags'])) {
                $this->invalidateTags($invalidateCache['tags']);
            }

            if (isset($invalidateCache['versions'])) {
                $this->invalidateVersions($invalidateCache['versions']);
            }
        }

        return $next($request);
    }

    protected function invalidateKeys(array $keys)
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    protected function invalidateTags(array $tags)
    {
        foreach ($tags as $tag) {
            Cache::tags($tag)->flush();
        }
    }

    protected function invalidateVersions(array $versions)
    {
        foreach ($versions as $version) {
            Cache::tags('version:' . $version)->flush();
        }
    }
}
```

### Step 2: Register Middleware

Register the middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // Other middleware
    'invalidate.cache' => \App\Http\Middleware\ConditionalCacheInvalidation::class,
];
```

### Step 3: Apply Middleware to Legacy Routes

You can apply the middleware to specific routes in your legacy router. Here’s how you can do it:

1. **Access the Router**: Access the legacy router file where your routes are defined.
2. **Apply Middleware**: Use the `middleware` method to apply the `invalidate.cache` middleware to the legacy routes.

#### Example in a Legacy Router

If your legacy routes are defined in a separate file, you can apply the middleware like this:

```php
use Illuminate\Support\Facades\Route;

// Apply middleware to a specific route
Route::middleware(['invalidate.cache'])->get('/legacy-route', function () {
    // Your legacy route logic
});

// Apply middleware to a group of routes
Route::middleware(['invalidate.cache'])->group(function () {
    Route::get('/legacy-route1', function () {
        // Your legacy route logic
    });

    Route::get('/legacy-route2', function () {
        // Your legacy route logic
    });
});
```

### Applying Middleware Directly in Legacy Code

If your legacy routing system does not use Laravel's routing, you might need to apply the middleware manually:

#### Example in a Legacy Router

```php
use App\Http\Middleware\ConditionalCacheInvalidation;
use Illuminate\Http\Request;

// Simulate a request in your legacy router
$request = Request::capture();

// Instantiate the middleware
$middleware = new ConditionalCacheInvalidation();

// Apply the middleware
$response = $middleware->handle($request, function ($request) {
    // Your legacy route logic
    return response('Legacy response');
});

// Send the response
$response->send();
```

### Conclusion

By following these steps, you can apply the `ConditionalCacheInvalidation` middleware to your legacy routes, ensuring that cache invalidation based on HTTP headers works seamlessly during your migration process. This approach allows you to leverage Laravel's middleware functionality even in a legacy routing setup.


<?php

namespace Itjonction\Blockcache;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Itjonction\Blockcache\Blade\CacheManager;

class BlockCacheServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Blade::directive('cache', function ($expression) {
            return "<?php if ( ! app('Itjonction\Blockcache\BladeDirective')->setUp({$expression}) ) { ?>";
        });

        Blade::directive('endcache', function () {
            return "<?php } echo app('Itjonction\Blockcache\BladeDirective')->tearDown() ?>";
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BladeDirective::class, function () {
            return new BladeDirective(
                new CacheManager($this->app->make(Cache::class))
            );
        });
    }
}

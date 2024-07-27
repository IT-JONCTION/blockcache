<?php

namespace Itjonction\Blockcache;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BlockCacheServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Blade::directive('cache', function ($expression) {
            return "<?php if ( !Itjonction\Blockcache\BlockCaching::setUp({$expression}) ) { ?>";
        });

        Blade::directive('endcache', function () {
            return "<?php } echo Itjonction\Blockcache\BlockCaching::tearDown() ?>";
        });
    }
}

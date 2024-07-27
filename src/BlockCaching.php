<?php

namespace Itjonction\Blockcache;

use Illuminate\Support\Facades\Cache;

class BlockCaching
{
    protected static array $keys = [];
    public static function setUp($model)
    {
        // generate a unique key for the model
       static::$keys[] = $key = $model->getCacheKey();
        // turn on output buffering
        ob_start();
        // return a boolean value that indicates whether the model is cached
        return Cache::tags('views')->has($key);
    }

    public static function tearDown()
    {
        // get the last key from the keys array
        $key = array_pop(static::$keys);
        // get the contents of the output buffer
        $html = ob_get_clean();
        // cache it, if necessary, and echo out the contents
        return Cache::tags('views')->rememberForever($key, function () use ($html) {
            return $html;
        });
    }
}

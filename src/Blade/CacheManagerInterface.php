<?php

namespace Itjonction\Blockcache\Blade;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;

interface CacheManagerInterface
{
    public function __construct(Repository $cache);

    public function put($key, $fragment);

    public function has($key);
}

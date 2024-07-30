<?php

namespace Itjonction\Blockcache\Contracts;

use Illuminate\Contracts\Cache\Repository;

interface ManagesCaches
{
    public function __construct(Repository $cache);

    public function put($key, $fragment);

    public function has($key);
}

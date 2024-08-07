<?php

namespace Itjonction\Blockcache\Contracts;

use Illuminate\Contracts\Cache\Repository;

interface ManagesCaches
{
    public function __construct(Repository $cache);

    public function remember($key, $fragment, int | null $ttl = null, string | array $tags = 'views'): string;

    public function has($key, $tags = null): bool;
}

<?php

namespace Itjonction\Blockcache\General;

use Exception;
use Itjonction\Blockcache\BladeDirective;
use Itjonction\Blockcache\Blade\CacheManager as BladeCacheManager;

class CacheManager extends BladeCacheManager
{
    protected BladeDirective $bladeDirective;

    public function __construct($cache)
    {
        $this->bladeDirective = new BladeDirective($this);
        parent::__construct($cache);
    }

    /**
     * @throws Exception
     */
    public function startCache($key, array $options = []): bool
    {
        return $this->bladeDirective->setUp($key, $options);
    }

    public function endCache(): bool|string|null
    {
        return $this->bladeDirective->tearDown();
    }
}

<?php

namespace Itjonction\Blockcache\General;

use Exception;
use Itjonction\Blockcache\BladeDirective;

class CacheManager
{
    protected BladeDirective $bladeDirective;

    public function __construct(BladeDirective $bladeDirective)
    {
        $this->bladeDirective = $bladeDirective;
    }

    /**
     * @throws Exception
     */
    public function startCache($key)
    {
        return $this->bladeDirective->setUp($key);
    }

    public function endCache()
    {
        return $this->bladeDirective->tearDown();
    }
}

<?php

namespace Itjonction\Blockcache;

trait HasCacheKey
{
    //get cache key method based on the model's updated_at timestamp
    public function getCacheKey(): string
    {
        return sprintf("%s/%s-%s",
            get_class($this),
            $this->id,
            $this->updated_at->timestamp
        );
    }
}

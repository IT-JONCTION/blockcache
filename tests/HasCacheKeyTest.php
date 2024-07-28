<?php
class HasCacheKeyTest extends TestCase
{
    function test_it_gets_a_unique_cache_key_for_an_eloquent_model()
    {
        //I have a model that uses the HasCacheKey trait
        $model = $this->makePost();
        $key = $model->getCacheKey();
        //I need to verify that the cache key is unique
        $this->assertNotEquals($key, ($this->makePost()->getCacheKey()));
        //I need to verify that the cache key follows expected format
        $this->assertEquals('Post/1-'.$model->updated_at->timestamp, $key);
    }
}

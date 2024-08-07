<?php

namespace Itjonction\Blockcache;

use Exception;
use Illuminate\Support\Collection;
use Itjonction\Blockcache\Contracts\ManagesCaches;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class BladeDirective
{
    protected array $keys = [];
    protected array $options = [];
    protected int $ttl;
    protected ManagesCaches $cache;
    private Logger $logger;

    public function __construct(ManagesCaches $cache, Logger $logger = null)
    {
        $this->cache = $cache;
        $this->logger = $logger ?: new Logger('blockcache');
        if (!$logger) {
            $this->logger->pushHandler(new NullHandler());
        }
    }

    /**
     * @throws Exception
     */
    public function setUp($keyOrModel, array $options = []): bool
    {
        ob_start();
        $this->options = $options;
        try {
            $this->keys[] = $key = $this->normalizeKey($keyOrModel); //it's an array so we can nest the cache
            return $this->cache->has($key);
        } catch (Exception $e) {
            // don't allow exceptions to bubble up as they will break the view
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function tearDown(): false|string|null
    {
        $localKeys = $this->keys;
        foreach ($this->options as $strategy => $value) {
            try {
                return $this->applyCacheStrategy($strategy, array_pop($localKeys), $value);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                // If the strategy failed, we refuse to cache and return the output.
                if (ob_get_level() > 0) {
                    return ob_get_clean();
                }
            }
        }
        // If no strategy was provided, we'll default to
        if (ob_get_level() > 0) {
            return $this->cache->remember(
              array_pop($this->keys), ob_get_clean()
            );
        }
        return '';
    }

    /**
     * Normalize the cache key.
     *
     * @param  mixed  $item
     * @return mixed|string|null
     * @throws Exception
     */
    protected function normalizeKey(mixed $item): mixed
    {
        // User provided a string - so that is the key.
        if (is_string($item) ) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'getCacheKey')) {
            return $item->getCacheKey();
        }

        // If a collection, the key is a hash of its contents.
        if ($item instanceof Collection) {
            return md5($item);
        }

        throw new Exception('Could not determine an appropriate cache key.');
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @param  int|string  $strategy
     * @param  mixed  $key
     * @param  mixed  $value
     * @return false|string|null
     * @throws Exception
     */
    public function applyCacheStrategy(int|string $strategy, mixed $key, mixed $value): false|string|null
    {
        if (ob_get_level() > 0) {
            return match ($strategy) {
                'tags' => $this->cache->remember($key, ob_get_clean(), null, $value),
                'ttl' => $this->cache->remember($key, ob_get_clean(), $this->normalizeTtl($value)),
                'version' => $this->cache->remember($key.'/v'.$value, ob_get_clean()),
                default => throw new Exception('Unknown strategy: '.$strategy),
            };
        }
        return '';
    }

    private function normalizeTtl(mixed $value)
    {
        return $this->ttl = is_array($value) ? rand(...$value) : $value;
    }
}

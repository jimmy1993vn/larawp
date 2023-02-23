<?php

namespace LaraPress\Console\Scheduling;

use DateTimeInterface;
use LaraPress\Contracts\Cache\Factory as Cache;

class CacheSchedulingMutex implements SchedulingMutex, CacheAware
{
    /**
     * The cache factory implementation.
     *
     * @var \LaraPress\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new scheduling strategy.
     *
     * @param \LaraPress\Contracts\Cache\Factory $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param \LaraPress\Console\Scheduling\Event $event
     * @param \DateTimeInterface $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->add(
            $event->mutexName() . $time->format('Hi'), true, 3600
        );
    }

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param \LaraPress\Console\Scheduling\Event $event
     * @param \DateTimeInterface $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->has(
            $event->mutexName() . $time->format('Hi')
        );
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param string $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}

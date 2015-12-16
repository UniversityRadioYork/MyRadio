<?php

namespace MyRadio\Iface;

/**
 * A standard interface for cache systems. This should allow them to easily be
 * swapped out later (MemcachedProvider, APCProvider, PsqlProvider, FileProvider...).
 */
interface CacheProvider extends Singleton
{
    /**
     * Inserts or Updates a cache entry.
     *
     * @param int $expires The number of seconds the cache is valid for
     *                     0 is forever.
     *                     Performs no action if cache disabled
     */
    public function set($key, $value, $expires = 0);
    /**
     * Gets a cache entry.
     *
     * @return false if not exists, or cache disabled
     */
    public function get($key);
    /**
     * Gets all cache entries using the given keys.
     *
     * @return array
     */
    public function getAll($keys);
    /**
     * Deletes a cache entry.
     */
    public function delete($key);
    /**
     * Empties the cache.
     */
    public function purge();
}

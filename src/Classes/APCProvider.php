<?php
/**
 * This file contains the APCProvider class. The current CacheProvider is loaded as part of the bootstrap process,
 * as configured in the Config.
 */
namespace MyRadio;

/**
 * APCProvider provides in-memory caching for PHP resources to increase page load times.
 *
 * APCProvider was the first CacheProvider implementation in MyRadio. It enables Models to send cache commands to it
 * which are then stored using the APC plugin automatically. In order for this class to work correctly, the server
 * needs the APC PHP plugin installed on the server. It will throw an Exception if it is not.
 */
class APCProvider implements \MyRadio\Iface\CacheProvider
{
    /**
     * A variable to store the singleton instance.
     *
     * @var APCProvider storage for the only APCProvider instance
     */
    private static $me;

    /**
     * Stores whether caching should be used. If not, it does not do anything on function calls.
     *
     * @var bool
     */
    private $enable;

    /**
     * Constructs the Unique instance of the CacheProvider for use. Private so that instances cannot be used in ways
     * other than those intended.
     *
     * @param bool $enable Whether caching is actually enabled in this request. Default true
     *
     * @throws MyRadioException Will throw a MyRadioException if the APC extension is not loaded
     */
    private function __construct($enable = true)
    {
        $this->enable = $enable;
        if ($enable && !function_exists('apc_store')) {
            //Functions not available. If this is caught upstream, just disable
            throw new MyRadioException('Cache is enabled but selected CacheProvider does not have required '
                                       . 'prerequisites (Is APC Extension installed and loaded?)');
            $this->enable = false;
        }
    }

    /**
     * Stores an object in the APC User Object Cache.
     *
     * @param string $key     The unique name of the object to store. Ideally, this would use myury_{module}_{name}
     * @param mixed  $value   The data to store
     * @param int    $expires The number of seconds this cache entry is valid for. Default is value of
     *                        MyRadio_Config::$cache_default_timeout
     *
     * @return bool Whether the operation was successful (returns false if caching disabled)
     * @assert ('myradio_core_test', 'test value', 0) == true
     */
    public function set($key, $value, $expires = 0)
    {
        if (!$this->enable) {
            return false;
        }

        if ($expires === 0) {
            $expires = \MyRadio\Config::$cache_default_timeout;
        }

        return apc_store($this->getKeyPrefix().$key, $value, $expires);
    }

    /**
     * Reads a previously stored value from the APC User Object Cache and returns it.
     *
     * @param string $key The unique name of the object to fetch
     *
     * @return mixed The value of the store, or false on failure
     * @assert ('myradio_core_test') == 'test value'
     */
    public function get($key)
    {
        if (!$this->enable) {
            return false;
        }

        return apc_fetch($this->getKeyPrefix().$key);
    }

    public function getAll($keys)
    {
        if (!$this->enable) {
            return [];
        }

        $result = [];

        foreach ($keys as $key) {
            $result[] = $this->get($key);
        }

        return $result;
    }

    /**
     * Deletes a previously stored value from the APC User Object Cache.
     *
     * @param string $key The unique name of the object to delete
     *
     * @return bool Returns whether the operaion was a success
     * @assert ('myradio_core_test') == true
     */
    public function delete($key)
    {
        if (!$this->enable) {
            return false;
        }

        return apc_delete($this->getKeyPrefix().$key);
    }

    /**
     * This will completely wipe the APC User Object Cache.
     */
    public function purge()
    {
        if (!$this->enable) {
            return false;
        }
        apc_clear_cache('user');

        return true;
    }

    /**
     * Returns the Singleton instance of this class, creating it if necessary.
     *
     * @return APCProvider
     */
    public static function getInstance()
    {
        if (!self::$me) {
            self::$me = new self(Config::$cache_enable);
        }

        return self::$me;
    }

    /**
     * Prevent copies being unintentionally made.
     *
     * @throws MyRadioException
     */
    public function __clone()
    {
        throw new \MyRadio\MyRadioException('Attempted to clone a singleton');
    }

    public function getKeyPrefix()
    {
        return 'MyRadioCache-';
    }
}

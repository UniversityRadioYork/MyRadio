<?php
/**
 * This file provides the ServiceAPI abstract class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\DebugCacheProvider;
use MyRadio\Iface\CacheProvider;
use MyRadio\MyRadioException;

/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyRadio integration.
 *
 * @uses    \Database
 * @uses    \CacheProvider
 */
abstract class ServiceAPI
{
    /**
     * All ServiceAPI subclasses will contain a reference to the Database Singleton.
     *
     * @var Database
     */
    protected static $db = null;
    /**
     * All ServiceAPI subclasses will contain a reference to the CacheProvider Singleton.
     *
     * @var CacheProvider
     */
    protected static $cache = null;

    protected $change = false;

    /**
     * Start up the connection to the Database.
     */
    protected static function initDB()
    {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
    }

    /**
     * Start up the connection to the CacheProvider.
     */
    protected static function initCache()
    {
        if (!self::$cache) {
            $cache = Config::$cache_provider;
            self::$cache = $cache::getInstance();
            if ($_REQUEST['debugCache'] === 'true' || $_SERVER['HTTP_X_MYRADIO_DEBUG_CACHE'] === 'true') {
                self::$cache = new DebugCacheProvider(self::$cache);
            }
        }
    }

    /**
     * A magic function that will reload the Database and CacheProvider after the object has been loaded from Cache.
     */
    public function __wakeup()
    {
        self::wakeup();
    }

    public static function wakeup()
    {
        self::initDB();
        self::initCache();
    }

    /**
     * Loads an instance of this object from either the cache or the DB.
     * @param $itemid
     * @return static
     */
    public static function getInstance($itemid)
    {
        self::initCache();
        self::initDB();

        $class = get_called_class();
        $key = self::getCacheKey($itemid);
        $cache = self::$cache->get($key);
        if (!$cache) {
            $cache = $class::factory($itemid);
            self::$cache->set($key, $cache);
        }

        return $cache;
    }

    protected static function factory($itemid)
    {
        return new static($itemid);
    }

    protected function addMixins(&$data, $mixins, $mixin_funcs, $strict = true)
    {
        foreach ($mixins as $mixin) {
            if (array_key_exists($mixin, $mixin_funcs)) {
                $mixin_funcs[$mixin]($data);
            } else {
                throw new MyRadioException('Unsupported mixin '.$mixin, 400);
            }
        }
    }

    /**
     * Base method for serialising the class.
     * @param array $mixins Mixins
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        throw new MyRadioException(
            get_called_class() . ' has not had a DataSource Conversion Method Defined!',
            500
        );
    }

    public function __toString()
    {
        return get_called_class().'-'.$this->getID();
    }

    /**
     * Takes an array of IDs, and creates an array of the relevant objects.
     *
     * @param int[] $ids
     *
     * @return static[]
     */
    public static function resultSetToObjArray($ids): array
    {
        $response = [];
        $child = get_called_class();
        if (!is_array($ids) or empty($ids)) {
            return [];
        }
        foreach ($ids as $id) {
            $response[] = $child::getInstance($id);
        }

        return $response;
    }

    /**
     * Get the name of this ServiceAPI in the GraphQL schema.
     * @return string
     */
    public static function getGraphQLTypeName()
    {
        throw new MyRadioException('Tried to call getGraphQLTypeName on a type that it shouldn\'t be called on!');
    }

    protected function __construct()
    {
    }

    public function __destruct()
    {
        if ($this->change) {
            $this->write();
        }
    }

    /**
     * Generates the Key string for caching services.
     *
     * @param int $id The ID of the object to get the cache key for
     *
     * @return string
     */
    public static function getCacheKey($id)
    {
        return get_called_class().'-'.$id;
    }

    /**
     * Sets the cache for this object to be the current object state.
     *
     * This should always be called after a setSomething.
     * @param bool $forceWrite whether to immediately write to the cache, or wait until the object is destroyed
     */
    protected function updateCacheObject(bool $forceWrite = false)
    {
        if ($forceWrite) {
            $this->write();
        } else {
            $this->change = true;
        }
    }

    /**
     * Writes this object to the cache.
     */
    private function write(): void
    {
        $this->change = false;
        self::$cache->set(self::getCacheKey($this->getID()), $this);
    }

    /**
     * Removes singleton instance. Used for memory optimisation for very large
     * requests.
     *
     * @deprecated
     */
    public function removeInstance()
    {
        return true;
        unset(self::$singletons[self::getCacheKey($this->getID())]);
    }

    /**
     * If cache debugging is enabled, returns the action log.
     * @return array|null
     */
    public static function getCacheDebugLog() {
        if (self::$cache instanceof DebugCacheProvider) {
            return self::$cache->getActions();
        }
        return null;
    }

}

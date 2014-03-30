<?php
/**
 * This file provides the ServiceAPI abstract class for MyRadio
 * @package MyRadio_Core
 */

/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyRadio integration
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130707
 * @package MyRadio_Core
 * @uses \Database
 * @uses \CacheProvider
 */
abstract class ServiceAPI implements IServiceAPI, MyRadio_DataSource
{
    /**
     * All ServiceAPI subclasses will contain a reference to the Database Singleton
     * @var \Database
     */
    protected static $db = null;
    /**
     * All ServiceAPI subclasses will contain a reference to the CacheProvider Singleton
     * @var \CacheProvider
     */
    protected static $cache = null;

    /**
     * Start up the connection to the Database
     */
    protected static function initDB()
    {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
    }

    /**
     * Start up the connection to the CacheProvider
     */
    protected static function initCache()
    {
        if (!self::$cache) {
            $cache = Config::$cache_provider;
            self::$cache = $cache::getInstance();
        }
    }

    /**
     * A magic function that will reload the Database and CacheProvider after the object has been loaded from Cache
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

    public static function getInstance($itemid)
    {
        self::initCache();
        self::initDB();

        $class = get_called_class();
        $key = $class::getCacheKey($itemid);
        $cache = self::$cache->get($key);
        if (!$cache) {
            $cache = new $class($itemid);
            self::$cache->set($key, $cache, 86400);
        }

        return $cache;
    }

    public function toDataSource($full = false)
    {
        throw new MyRadioException(get_called_class() . ' has not had a DataSource Conversion Method Defined!', 500);
    }

    /**
     * Iteratively calls the toDataSource method on all of the objects in the given array, returning the results as
     * a new array.
     * @param Array $array
     * @param bool $full If true, will return expanded data if available.
     * @return Array
     * @throws MyRadioException Throws an Exception if a provided object is not a DataSource
     */
    public static function setToDataSource($array, $full = false)
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = array();
        foreach ($array as $element) {
            //It must implement the toDataSource method!
            if (!method_exists($element, 'toDataSource')) {
                throw new MyRadioException('Attempted to convert '.get_class($element).' to a DataSource but it not a valid Data Object!', 500);
            } else {
                $result[] = $element->toDataSource($full);
            }
        }

        return $result;
    }

    public function __toString()
    {
        return get_called_class().'-'.$this->getID();
    }

    /**
     * Takes an array of IDs, and creates an array of the relevant objects
     * @param int[] $ids
     * @return ServiceAPI[]
     */
    public static function resultSetToObjArray($ids)
    {
        $response = array();
        $child = get_called_class();
        if (!is_array($ids) or empty($ids)) {
            return [];
        }
        foreach ($ids as $id) {
            $response[] = $child::getInstance($id);
        }

        return $response;
    }

    protected function __construct()
    {

    }

    /**
     * Generates the Key string for caching services
     *
     * @param int $id The ID of the object to get the cache key for
     * @return String
     */
    public static function getCacheKey($id)
    {
        return get_called_class() . '-' . $id;
    }

    /**
     * Sets the cache for this object to be the current object state.
     *
     * This should always be called after a setSomething.
     */
    protected function updateCacheObject()
    {
        self::$cache->set(self::getCacheKey($this->getID()), $this, 3600);
    }

    /**
     * Removes singleton instance. Used for memory optimisation for very large
     * requests.
     * @deprecated
     */
    public function removeInstance()
    {
        return true;
        unset(self::$singletons[self::getCacheKey($this->getID())]);
    }
}

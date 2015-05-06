<?php
/**
 * This file provides the ServiceAPI abstract class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\Iface\IServiceAPI;
use \MyRadio\Iface\MyRadio_DataSource;

use \MyRadio\Traits\Configurable;
use \MyRadio\Traits\DatabaseSubject;

use \MyRadio\MyRadioException;

/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyRadio integration
 *
 * @package MyRadio_Core
 * @uses    \Database
 * @uses    \CacheProvider
 */
abstract class ServiceAPI implements IServiceAPI, MyRadio_DataSource
{
    use Configurable;
    use DatabaseSubject;

    public static function getInstance($itemid, $container)
    {
        //TODO: Fix caching
        $cacheProvider = null;

        $class = get_called_class();
        $key = self::getCacheKey($itemid);
        $cache = $cacheProvider ? $cacheProvider->get($key) : false;
        if (!$cache) {
            $cache = $class::factory($itemid, $container);
            $cacheProvider->set($key, $cache);
        }

        return $cache;
    }

    protected static function factory($itemid, $container)
    {
        $class = get_called_class();
        return $container->newInstance($class, [ $itemid, $container->get('database') ]);
    }

    public function toDataSource($full = false)
    {
        throw new MyRadioException(get_called_class() . ' has not had a DataSource Conversion Method Defined!', 500);
    }

    /**
     * Iteratively calls the toDataSource method on all of the objects in the given array, returning the results as
     * a new array.
     * @param Array $array
     * @param bool  $full  If true, will return expanded data if available.
     * @return Array
     * @throws MyRadioException Throws an Exception if a provided object is not a DataSource
     */
    public static function setToDataSource($array, $full = false)
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = [];
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

    protected function __construct()
    {

    }

    /**
     * Generates the Key string for caching services
     *
     * @param  int $id The ID of the object to get the cache key for
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
        self::$container['cache']->set(self::getCacheKey($this->getID()), $this);
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
}

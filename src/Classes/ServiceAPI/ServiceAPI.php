<?php
/**
 * This file provides the ServiceAPI abstract class for MyURY
 * @package MyURY_Core
 */

/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyURY integration
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130707
 * @package MyURY_Core
 * @uses \Database
 * @uses \CacheProvider
 */
abstract class ServiceAPI implements IServiceAPI, MyURY_DataSource {
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
  protected static function initDB() {
    if (!self::$db) {
      self::$db = Database::getInstance();
    }
  }
  
  /**
   * Start up the connection to the CacheProvider
   */
  protected static function initCache() {
    if (!self::$cache) {
      $cache = Config::$cache_provider;
      self::$cache = $cache::getInstance();
    }
  }
  
  /**
   * A magic function that will reload the Database and CacheProvider after the object has been loaded from Cache
   */
  public function __wakeup() {
    self::initDB();
    self::initCache();
  }
  
  public static function getInstance($itemid = -1) {
    throw new MyURYException(__CLASS__ . ' is not an initialisable Service API!', MyURYException::FATAL);
    return $itemid;
  }
  
  public function toDataSource() {
    throw new MyURYException(__CLASS__ . ' has not had a DataSource Conversion Method Defined!', MyURYException::FATAL);
  }
  
  /**
   * Iteratively calls the toDataSource method on all of the objects in the given array, returning the results as
   * a new array.
   * @param Array $array
   * @return Array
   * @throws MyURYException Throws an Exception if a provided object is not a DataSource
   */
  public static function setToDataSource($array) {
    $result = array();
    foreach ($array as $element) {
      //It must implement the toDataSource method!
      if (!method_exists($element, 'toDataSource')) {
        throw new MyURYException('Attempted to convert '.get_class($element).' to a DataSource but it not a valid Data Object!', MyURYException::FATAL);
      } else {
        $result[] = $element->toDataSource();
      }
    }
    return $result;
  }
  
  public function __toString() {
    return get_called_class().'-'.$this->getID();
  }
  
  /**
   * Takes an array of IDs, and creates an array of the relevant objects
   * @param int[] $ids
   * @return ServiceAPI[]
   */
  protected static function resultSetToObjArray($ids) {
    $response = array();
    $child = get_called_class();
    foreach ($ids as $id) {
      $response[] = $child::getInstance($id);
    }
    
    return $response;
  }
}
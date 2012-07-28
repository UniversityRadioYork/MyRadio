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
 * @version 21072012
 * @package MyURY_Core
 * @uses \Database
 * @uses \CacheProvider
 */
abstract class ServiceAPI implements IServiceAPI {
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
  
  public function getInstance($itemid = -1) {
    throw new MyURYException(__CLASS__ . ' is not an initialisable Service API!', MyURYException::$fatal);
  }
}
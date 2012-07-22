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
 * @uses Database
 * @uses CacheProvider
 */
abstract class ServiceAPI implements IServiceAPI {
  protected static $db = null;
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
}
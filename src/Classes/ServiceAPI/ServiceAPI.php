<?php
/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyURY integration
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
abstract class ServiceAPI implements IServiceAPI {
  protected static $db = null;
  protected static $cache = null;
  
  protected static function initDB() {
    if (!self::$db) {
      self::$db = Database::getInstance();
    }
  }
  
  protected static function initCache() {
    if (!self::$cache) {
      $cache = Config::$cache_provider;
      self::$cache = $cache::getInstance();
    }
  }
  
  public function __wakeup() {
    self::initDB();
    self::initCache();
  }
}
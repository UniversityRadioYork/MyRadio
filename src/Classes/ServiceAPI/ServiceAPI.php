<?php
/**
 * An Abstract superclass for ServiceAPI classes that implements essential
 * base functionality for full MyURY integration
 *
 * @author lpw
 */
abstract class ServiceAPI implements ServiceAPI {
  protected static $db = null;
  protected static $cache = null;
  
  private static function initDB() {
    if (!self::$db) {
      self::$db = Database::getInstance();
    }
  }
  
  private static function initCache() {
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
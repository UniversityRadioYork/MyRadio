<?php
/**
 * Description of APCProvider
 *
 * @author lpw
 */
class APCProvider implements CacheProvider {
  private static $me;
  private $enable;
  
  private function __construct($enable = true) {
    $this->enable = $enable;
    if ($enable && !function_exists('apc_store')) {
      //Functions not available. If this is caught upstream, just disable
      throw new MyURYException('Cache is enabled but selected CacheProvider does not have required prerequisites (Is APC Extension installed and loaded?)');
      $this->enable = false;
    }
  }
  
  public function set($key, $value, $expires = 0) {
    if (!$this->enable) return false;
    return apc_store($key, $value, $expires);
  }
  
  public function get($key) {
    if (!$this->enable) return false;
    return apc_fetch($key);
  }
  
  public function delete($key) {
    if (!$this->enable) return false;
    return apc_delete($key);
  }
  
  /**
   * @todo find a way to implement this in APC
   */
  public function purge() {
    if (!$this->enable) return false;
    return true;
  }
  
  public static function getInstance() {
    if (!self::$me) {
      self::$me = new self(Config::$cache_enable);
    }
    return self::$me;
  }
}


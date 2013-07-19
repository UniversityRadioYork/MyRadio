<?php

abstract class MyURY_Daemon {
  public static function isEnabled() { return false; }
  public static function run() {throw new MyURYException('NO RUN METHOD FOR '.get_called_class().'!');}
  
  protected static function getCache($key) {
    $prov = Config::$cache_provider;
    return $prov::getInstance()->get($key);
  }
  
  protected static function setCache($key, $value, $expire = null) {
    $prov = Config::$cache_provider;
    return $prov::getInstance()->set($key, $value, $expire);
  }
}
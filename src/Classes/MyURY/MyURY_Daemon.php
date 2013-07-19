<?php

abstract class MyURY_Daemon {
  public static function isEnabled() { return false; }
  public static function run() {throw new MyURYException('NO RUN METHOD FOR '.get_called_class().'!');}
  
  protected static function getVal($key) {
    return json_decode(file_get_contents(Config::$daemon_lock_file), true)[$key];
  }
  
  protected static function setVal($key, $value) {
    $data = json_decode(file_get_contents(Config::$daemon_lock_file), true);
    $data[$key] = $value;
    
    file_put_contents(Config::$daemon_lock_file, json_encode($data));
  }
}
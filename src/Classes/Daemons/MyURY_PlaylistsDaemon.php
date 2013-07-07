<?php

class MyURY_PlaylistsDaemon {
  private static $lastrun = 0;
  
  public static function isEnabled() { return false; }
  
  public static function run() {
    if (self::$lastrun > time() - 3600) return;
    
    //TODO
    
    
    //Done
    self::$lastrun = time();
  }
}
<?php
/**
 * This Daemon creates new URY Member accounts based on data from the YUSU API
 */
class MyURY_MemberSyncDaemon {
  private static $lastrun = 0;
  
  public static function isEnabled() { return false; }
  
  public static function run() {
    if (self::$lastrun > time() - 3600) return;
    
    $members = CoreUtils::callYUSU('ListMembers');
    
    print_r($members);
    
    //Done
    //self::$lastrun = time();
  }
}
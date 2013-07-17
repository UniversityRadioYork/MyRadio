<?php
/**
 * This Daemon creates new URY Member accounts based on data from the YUSU API
 */
class MyURY_MemberSyncDaemon {
  public static function isEnabled() { return false; }
  
  public static function run() {
    $hourkey = __CLASS__.'_last_run_hourly';
    if (self::getCache($hourkey) > time() - 3500) return;
    
    $members = CoreUtils::callYUSU('ListMembers');
    
    print_r($members);
    
    //Done
    self::setCache($hourkey, time());
  }
}
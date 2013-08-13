<?php
/**
 * This Daemon creates new URY Member accounts based on data from the YUSU API
 */
class MyURY_MemberSyncDaemon extends MyURY_Daemon {
  public static function isEnabled() { return true; }
  
  public static function run() {
    $hourkey = __CLASS__.'_last_run_hourly';
    if (self::getVal($hourkey) > time() - 3500) {
      return;
    }
    
    $members = CoreUtils::callYUSU('ListMembers');
    
    foreach ($members as $member) {
      dlog('Checking YUSU Member '.$member['name'], 4);
      print_r($member);
    }
    
    //Done
    self::setVal($hourkey, time());
  }
}
<?php
/**
 * This Daemon creates new URY Member accounts based on data from the YUSU API
 */
class MyURY_MemberSyncDaemon extends MyURY_Daemon {
  public static function isEnabled() { return Config::$d_MemberSync_enabled; }
  
  public static function run() {
    $hourkey = __CLASS__.'_last_run_hourly';
    if (self::getVal($hourkey) > time() - 3500) {
      return;
    }
    
    $members = CoreUtils::callYUSU('ListMembers');
    
    foreach ($members as $member) {
      dlog('Checking YUSU Member '.$member['EmailAddress'], 4);
      print_r($member);
      $result = User::findByEmail($member['EmailAddress']);
      
      if (empty($result)) {
        dlog('Member '.$member['EmailAddress'].' does not exist.', 3);
      } else {
        dlog('Member '.$member['EmailAddress'].' matches '.$result->getID().'.', 3);
        dlog('Setting '.$result->getID().' payment to '.Config::$membership_fee.'.', 2);
        $result->setPayment(Config::$membership_fee);
      }
    }
    
    //Done
    self::setVal($hourkey, time());
  }
}
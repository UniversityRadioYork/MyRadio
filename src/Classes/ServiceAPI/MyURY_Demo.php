<?php

/**
 * This file provides the Demo class for MyURY
 * @package MyURY_Demo
 */

/**
 * Abstractor for the Demo utilities
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24102012
 * @package MyURY_Demo
 * @uses \Database
 */
class MyURY_Demo extends MyURY_Scheduler_Common {

  public static function registerDemo($time) {
    date_default_timezone_set('UTC');
    self::initDB();

    /**
     * Demos use the timeslot member as the credit for simplicity
     */
    self::$db->query('INSERT INTO schedule.show_season_timeslot (show_season_id, start_time, memberid, approvedid, duration)
    VALUES (0, $1, $2, $2, \'01:00:00\')', array(CoreUtils::getTimestamp($time), $_SESSION['memberid']));
    date_default_timezone_set('Europe/London');
  }
  
  public static function attendingDemo($demoid) {
    return self::$db->num_rows(
            self::$db->query('SELECT creditid FROM schedule.show_credit WHERE show_id = 0 AND effective_from=$1 AND credit_type_id=7', array(self::getDemoTime($demoid))));
  }
  
  /**
   * Gets a list of available demo slots in the future
   */
  public static function listDemos() {
    self::initDB();
    $result = self::$db->fetch_all('SELECT show_season_timeslot_id, start_time, memberid FROM schedule.show_season_timeslot WHERE show_season_id = 0 AND start_time > NOW()');
    
    //Add the credits for each member
    $demos = array();
    foreach ($result as $demo) {
      $demo['start_time'] = date('d M H:i', strtotime($demo['start_time']));
      $demo['memberid'] = User::getInstance($demo['memberid'])->getName();
      $demos[] = array_merge($demo, array('attending' => self::attendingDemo($demo['show_season_timeslot_id'])));
    }
    
    return $demos;
  }
  
  /**
   * The current user is marked as attending a demo
   * Return 0: Success
   * Return 1: Demo Full
   * Return 2: *shrug*
   */
  public static function attend($demoid) {
    self::initDB();
    //Get # of attendees
    if (self::attendingDemo($demoid) >= 2) return 1;
    self::$db->query('INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from, effective_to, memberid, approvedid) VALUES
      (0, 7, $1, $2, $2, $1, $1)', array($_SESSION['memberid'], self::getDemoTime($demoid)));
    $time = self::getDemoTime($demoid);
    $user = self::getDemoer($demoid);
    MyURYEmail::sendEmail($user->getEmail(), 'New Demo Attendee', User::getInstance()->getName().' has joined your demo at '.$time.'.');
    return 0;
  }
  
  public static function getDemoTime($demoid) {
    self::initDB();
    $r = self::$db->fetch_column('SELECT start_time FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($demoid));
    return $r[0];
  }
  
  public static function getDemoer($demoid) {
    self::initDB();
    $r = self::$db->fetch_column('SELECT memberid FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($demoid));
    return User::getInstance($r[0]);
  }

}
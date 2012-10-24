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
  
  /**
   * Gets a list of available demo slots in the future
   */
  public static function listDemos() {
    self::initDB();
    $result = self::$db->fetch_all('SELECT show_season_timeslot_id, start_time, memberid FROM schedule.show_season_timeslot WHERE show_season_id = 0 AND start_time > NOW()');
    
    //Add the credits for each member
    $demos = array();
    foreach ($result as $demo) {
      $credits = self::$db->fetch_column('SELECT creditid FROM schedule.show_credit WHERE show_id = 0 AND effective_from=$1 AND credit_type_id=7', array($demo['start_time']));
      $demos = array_merge($demo, array('attending' => $credits));
    }
    
    return $demos;
  }

}
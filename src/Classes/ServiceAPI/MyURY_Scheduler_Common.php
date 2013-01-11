<?php

/*
 * Provides the Scheduler Common class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Scheduler_Common class is used to provide common resources for the MyURY Scheduler classes
 * @version 04092012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * 
 */

abstract class MyURY_Scheduler_Common extends ServiceAPI {

  protected static $metadata_keys = array();

  /**
   * Gets the id for the string representation of a type of metadata
   */
  public static function getMetadataKey($string) {
    self::cacheMetadataKeys();
    if (!isset(self::$metadata_keys[$string])) {
      throw new MyURYException('Metadata Key ' . $string . ' does not exist');
    }
    return self::$metadata_keys[$string]['id'];
  }

  /**
   * Gets whether the type of metadata is allowed to exist more than once
   */
  public static function isMetadataMultiple($id) {
    self::cacheMetadataKeys();
    foreach (self::$metadata_keys as $key) {
      if ($key['id'] == $id)
        return $key['multiple'];
    }
    throw new MyURYException('Metadata Key ID ' . $string . ' does not exist');
  }

  protected static function cacheMetadataKeys() {
    if (empty(self::$metadata_keys)) {
      self::initDB();
      $r = self::$db->fetch_all('SELECT metadata_key_id AS id, name, allow_multiple AS multiple FROM metadata.metadata_key');
      foreach ($r as $key) {
        self::$metadata_keys[$key['name']]['id'] = (int) $key['id'];
        self::$metadata_keys[$key['name']]['multiple'] = ($key['multiple'] === 't');
      }
    }
  }

  protected static function getCreditName($credit_id) {
    self::initDB();
    $r = self::$db->fetch_one('SELECT name FROM people.credit_type WHERE credit_type_id=$1 LIMIT 1', array((int) $credit_id));
    if (empty($r))
      return 'Contrib';
    return $r['name'];
  }

  protected static function formatTimeHuman($time) {
    date_default_timezone_set('UTC');
    $stime = date(' H:i', $time['start_time']);
    $etime = date('H:i', $time['start_time'] + $time['duration']);
    date_default_timezone_set('Europe/London');
    return self::getDayNameFromID($time['day']) . $stime . ' - ' . $etime;
  }

  protected static function getDayNameFromID($day) {
    switch ($day) {
      case 0:
        return 'Mon';
      case 1:
        return 'Tue';
      case 2:
        return 'Wed';
      case 3:
        return 'Thu';
      case 4:
        return 'Fri';
      case 5:
        return 'Sat';
      case 6:
        return 'Sun';
        break;
      default:
        throw new MyURYException('Invalid Day ID ' . $day);
    }
  }

  /**
   * 
   * @param int $term_id The term to check for
   * @param Array $time:
   * day: The day ID (0-6) to check for
   * start_time: The start time in seconds since midnight
   * duration: The duration in seconds
   * 
   * Return: Array of conflicts with week # as key and show as value
   */
  protected static function getScheduleConflicts($term_id, $time) {
    self::initDB();
    $conflicts = array();
    $date = MyURY_Scheduler::getTermStartDate($term_id);
    //Iterate over each week
    for ($i = 1; $i <= 10; $i++) {
      //Get the start and end times
      $start = $date + $time['start_time'];
      $end = $date + $time['start_time'] + $time['duration'];
      //Query for conflicts
      $r = self::getScheduleConflict($start, $end);
      
      //If there's a conflict, log it
      if (!empty($r)) $conflicts[$i] = $r['show_season_id'];
      
      //Increment week
      $date += 3600*24*7;
    }
    return $conflicts;
  }
  
  /**
   * Returns a schedule conflict between the given times, if one exists
   * @param int $start Start time
   * @param int $end End time
   * @return Array empty if no conflict, show information otherwise
   */
  protected static function getScheduleConflict($start, $end) {
    $start = CoreUtils::getTimestamp($start);
    $end = CoreUtils::getTimestamp($end);
    
    return self::$db->fetch_one('SELECT show_season_id FROM schedule.show_season_timeslot
        WHERE (start_time <= $1 AND start_time + duration > $1)
        OR (start_time > $1 AND start_time < $2)', array($start, $end));
    
  }
  
  /**
   * Returns the Term currently available for Season applications.
   * Users can only apply to the current term, or one week before the next one
   * starts.
   * 
   * @return int|null Returns the id of the term or null if no active term
   */
  public static function getActiveApplicationTerm() {
    $return = self::$db->fetch_column('SELECT termid FROM terms
      WHERE start <= $1 AND finish >= NOW() LIMIT 1',
            array(CoreUtils::getTimestamp(strtotime('+14 Days'))));
    return $return[0];
  }

}

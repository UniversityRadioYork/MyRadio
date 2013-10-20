<?php

/**
 * Provides the Metadata Common class for MyURY
 * @package MyURY_Core
 */

/**
 * The Metadata_Common class is used to provide common resources for
 * URY assets that utilise the Metadata system.
 *
 * The metadata system is a used to attach common attributes to an item,
 * such as a title or description. It includes versioning in the form of
 * effective_from and effective_to field, storing a history of previous values.
 *
 * @version 20130815
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 *
 */
abstract class MyURY_Metadata_Common extends ServiceAPI {
  use MyURY_Creditable;
  use MyURY_MetadataSubject;

  /**
   * @todo This is a duplication of CoreUtils functionality.
   * @deprecated
   */
  protected static function formatTimeHuman($time) {
    date_default_timezone_set('UTC');
    $stime = date(' H:i', $time['start_time']);
    $etime = date('H:i', $time['start_time'] + $time['duration']);
    date_default_timezone_set('Europe/London');
    return self::getDayNameFromID($time['day']) . $stime . ' - ' . $etime;
  }

  /**
   * @todo Move this into CoreUtils.
   */
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
   *
   * @todo Move this into the relevant scheduler class
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
      if (!empty($r)) {
        $conflicts[$i] = $r['show_season_id'];
      }

      //Increment week
      $date += 3600 * 24 * 7;
    }
    return $conflicts;
  }

  /**
   * Returns a schedule conflict between the given times, if one exists
   * @param int $start Start time
   * @param int $end End time
   * @return Array empty if no conflict, show information otherwise
   *
   * @todo Move this into the relevant scheduler class
   */
  protected static function getScheduleConflict($start, $end) {
    $start = CoreUtils::getTimestamp($start);
    $end = CoreUtils::getTimestamp($end-1);
    print_r($start);
    echo '<br>';
    print_r($end);
    echo '<br>';

    return self::$db->fetch_one('SELECT show_season_timeslot_id,
        show_season_id, start_time, start_time+duration AS end_time,
        \'$1\' AS requested_start, \'$2\' AS requested_end
        FROM schedule.show_season_timeslot
        WHERE (start_time <= $1 AND start_time + duration > $1)
        OR (start_time > $1 AND start_time < $2)', array($start, $end));
  }

  /**
   * Returns the Term currently available for Season applications.
   * Users can only apply to the current term, or one week before the next one
   * starts.
   *
   * @return int|null Returns the id of the term or null if no active term
   *
   * @todo Move this into the relevant scheduler class or CoreUtils
   */
  public static function getActiveApplicationTerm() {
    $return = self::$db->fetch_column('SELECT termid FROM terms
      WHERE start <= $1 AND finish >= NOW() LIMIT 1', array(CoreUtils::getTimestamp(strtotime('+28 Days'))));
    return $return[0];
  }
}

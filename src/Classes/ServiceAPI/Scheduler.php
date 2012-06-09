<?php
/**
 * Abstractor for the Scheduler Module
 *
 * @author lpw
 */
class Scheduler extends ServiceAPI {
  
  private static function pendingAllocationsQuery() {
    return self::$db->query('SELECT * FROM sched_entry WHERE entryid NOT IN
      (SELECT entryid FROM sched_timeslot)');
  }
  
  /**
   * Returns the number of shows awaiting a timeslot allocation
   * @return type 
   */
  public static function countPendingAllocations() {
    return (int)self::$db->num_rows(self::pendingAllocationsQuery());
  }
  
  /**
   * @todo implement this
   * @return int Zero. 
   */
  public static function countPendingDisputes() {
    return 0;
  }
}

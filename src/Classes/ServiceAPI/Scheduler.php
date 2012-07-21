<?php
/**
 * Abstractor for the Scheduler Module
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
class Scheduler extends ServiceAPI {
  private static $pendingAllocationsResult = null;
  
  private static function pendingAllocationsQuery() {
    if (self::$pendingAllocationsResult === null) {
      self::$pendingAllocationsResult = 
        self::$db->query('SELECT sched_entry.entryid, summary, createddate, day || \' \' || starttime as requestedtime
        FROM sched_entry, sched_showdetail
        WHERE sched_entry.entryid = sched_showdetail.entryid
        AND sched_entry.entryid NOT IN (SELECT entryid FROM sched_timeslot)
        AND sched_entry.entryid NOT IN (SELECT entryid FROM sched_reject WHERE revokeddate IS NULL)
        AND entrytypeid=3
        ORDER BY createddate ASC');
    }
    
    return self::$pendingAllocationsResult;
  }
  
  /**
   * Returns the number of shows awaiting a timeslot allocation
   * @return int the number of pending shows 
   */
  public static function countPendingAllocations() {
    return (int)self::$db->num_rows(self::pendingAllocationsQuery());
  }
  
  /**
   * Returns all show requests awaiting a timeslot allocation
   * @return Array[Array] An array of arrays of shows pending allocation
   */
  public static function getPendingAllocations() {
    return self::$db->fetch_all(self::pendingAllocationsQuery());
  }
  
  /**
   * @todo implement this
   * @return int Zero. 
   */
  public static function countPendingDisputes() {
    return 0;
  }
  
  /**
   * Returns a list of terms in the present or future
   * @return Array[Array] an array of arrays of terms
   */
  public function getTerms() {
    return self::$db->fetch_all('SELECT termid, EXTRACT(EPOCH FROM start) AS start, descr
                          FROM terms
                          WHERE finish > now()
                          ORDER BY start ASC');
  }
  
  public static function getInstance($id = -1) {throw new MyURYException('Not Implemented');}
}

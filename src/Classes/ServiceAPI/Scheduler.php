<?php
/**
 * This file provides the Scheduler class for MyURY
 * @package MyURY_Scheduler
 */

/**
 * Abstractor for the Scheduler Module
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 03082012
 * @package MyURY_Scheduler
 * @uses \Database
 */
class Scheduler extends ServiceAPI {
  /**
   * This provides a temporary cache of the result from pendingAllocationsQuery
   * @var Array
   */
  private static $pendingAllocationsResult = null;
  
  /**
   * Returns an Array of pending show allocations.
   * @return Array A 2D array with each element as follows:
   * entryid: The unique id of the show application<br>
   * summary: The name of the show<br>
   * createddate: The time the application was made<br>
   * requestedtime: The primary requested time of the application<br>
   */
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
   * Return the number of show application disputes pending response from Master of Scheduling
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
  public static function getTerms() {
    return self::$db->fetch_all('SELECT termid, EXTRACT(EPOCH FROM start) AS start, descr
                          FROM terms
                          WHERE finish > now()
                          ORDER BY start ASC');
  }
  
  /**
   * Returns a list of potential genres, organised so they can be used as a SELECT MyURYFormField data source
   */
  public static function getGenres() {
    return self::$db->fetch_all('SELECT genre_id AS value, name AS text FROM schedule.genre ORDER BY name ASC');
  }
  
  /**
   * Returns a list of potential credit types, organsed so they can be used as a SELECT MyURYFormField data source
   */
  public static function getCreditTypes() {
    return self::$db->fetch_all('SELECT show_credit_type_id AS value, name AS text FROM schedule.show_credit_type ORDER BY name ASC');
  }
  
  /**
   * Returns an Array of Shows matching the given partial title
   * @param String $title A partial or total title to search for
   * @param int $limit The maximum number of shows to return
   * @return Array 2D with each first dimension an Array as follows:<br>
   * title: The title of the show<br>
   * show_id: The unique id of the show
   */
  public static function findShowByTitle($term, $limit) {
    self::initDB();
    return self::$db->fetch_all('SELECT schedule.show.show_id, metadata_value AS title
      FROM schedule.show, schedule.show_metadata
      WHERE schedule.show.show_id = schedule.show_metadata.show_id
      AND metadata_key_id IN (SELECT metadata_key_id FROM schedule.metadata_key WHERE name=\'title\')
      AND title ILIKE \'%\' || $1 || \'%\' LIMIT $2', array($term, $limit));
  }
}
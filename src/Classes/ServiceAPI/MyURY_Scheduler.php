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
 * @todo Dedicated Term class
 */
class MyURY_Scheduler extends MyURY_Scheduler_Common {
  /**
   * This provides a temporary cache of the result from pendingAllocationsQuery
   * @var Array
   */
  private static $pendingAllocationsResult = null;
  
  /**
   * Returns an Array of pending Season allocations.
   * @return Array An Array of MyURY_Season objects which do not have an allocated timeslot, ordered by time submitted
   * @todo Move to MyURY_Season?
   */
  private static function pendingAllocationsQuery() {
    if (self::$pendingAllocationsResult === null) {
      /**
       * Must not be null - otherwise it hasn't been submitted yet
       */
      $result = 
        self::$db->fetch_column('SELECT show_season_id FROM schedule.show_season
          WHERE show_season_id NOT IN (SELECT show_season_id FROM schedule.show_season_timeslot)
          AND submitted IS NOT NULL
          ORDER BY submitted ASC');
      
      self::$pendingAllocationsResult = array();
      foreach ($result as $application) {
        self::$pendingAllocationsResult[] = MyURY_Season::getInstance($application);
      }
    }
    
    return self::$pendingAllocationsResult;
  }
  
  /**
   * Returns the number of seasons awaiting a timeslot allocation
   * @return int the number of pending season allocations 
   */
  public static function countPendingAllocations() {
    return sizeof(self::pendingAllocationsQuery());
  }
  
  /**
   * Returns all show requests awaiting a timeslot allocation
   * @return Array[MyURY_Season] An array of Seasons of pending allocation
   */
  public static function getPendingAllocations() {
    return self::pendingAllocationsQuery();
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
  
  public static function getActiveApplicationTermInfo() {
    $termid = self::getActiveApplicationTerm();
    return array('termid' => $termid, 'descr' => self::getTermDescr($termid));
  }
  
  public static function getTermDescr($termid) {
    $return = self::$db->fetch_one('SELECT descr, start FROM terms WHERE termid=$1',
            array($termid));
    return $return['descr'] . date(' Y',strtotime($return['start']));
  }
  
  public static function getTermStartDate($term_id = null) {
    if ($term_id === null) $term_id = self::getActiveApplicationTerm();
    $result = self::$db->fetch_one('SELECT start FROM terms WHERE termid=$1', array($term_id));
    return strtotime($result['start']);
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
    return self::$db->fetch_all('SELECT credit_type_id AS value, name AS text FROM people.credit_type ORDER BY name ASC');
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
      AND metadata_value ILIKE \'%\' || $1 || \'%\' LIMIT $2', array($term, $limit));
  }
}
<?php
/**
 * This file provides the Scheduler class for MyRadio
 * @package MyRadio_Scheduler
 */

/**
 * Abstractor for the Scheduler Module
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130813
 * @package MyRadio_Scheduler
 * @uses \Database
 * @todo Dedicated Term class
 */
class MyRadio_Scheduler extends MyRadio_Metadata_Common {
  /**
   * This provides a temporary cache of the result from pendingAllocationsQuery
   * @var Array
   */
  private static $pendingAllocationsResult = null;
  
  /**
   * Returns an Array of pending Season allocations.
   * @return Array An Array of MyRadio_Season objects which do not have an allocated timeslot, ordered by time submitted
   * @todo Move to MyRadio_Season?
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
        self::$pendingAllocationsResult[] = MyRadio_Season::getInstance($application);
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
   * @return Array[MyRadio_Season] An array of Seasons of pending allocation
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
    if (empty($termid)) return null;
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
    /**
     * An extra hour is added here due to some issues with timezones and public.terms - some
     * terms are set to start at 11pm Sunday instead of Midnight Monday. It's annoying because then we convert it back.
     * If we didn't it's not the end of the world - the usage for this does not include time so just the date *should*
     * be sufficient.
     * @todo Fix terms database so it isn't silly.
     */
    return strtotime('Midnight '.date('d-m-Y',strtotime($result['start'])+3600));
  }
  
  /**
   * Returns a list of potential genres, organised so they can be used as a SELECT MyRadioFormField data source
   */
  public static function getGenres() {
    self::wakeup();
    return self::$db->fetch_all('SELECT genre_id AS value, name AS text FROM schedule.genre ORDER BY name ASC');
  }
  
  /**
   * Returns a list of potential credit types, organsed so they can be used as a SELECT MyRadioFormField data source
   */
  public static function getCreditTypes() {
    self::wakeup();
    return self::$db->fetch_all('SELECT credit_type_id AS value, name AS text'
            . ' FROM people.credit_type ORDER BY name ASC');
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
      AND metadata_key_id IN (SELECT metadata_key_id FROM metadata.metadata_key WHERE name=\'title\')
      AND metadata_value ILIKE \'%\' || $1 || \'%\' LIMIT $2', array($term, $limit));
  }
  
  /**
   * @todo This probably shouldn't implement ServiceAPI
   */
  public function getID() {
    return 0;
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

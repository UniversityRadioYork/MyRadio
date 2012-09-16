<?php
/*
 * Provides the Season class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Season class is used to create, view and manupulate Seasons within the new MyURY Scheduler Format
 * @version 12082012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * @uses \MyURY_Show
 * 
 */
class MyURY_Season extends MyURY_Scheduler_Common {

  private static $seasons = array();
  private $season_id;
  
  public static function getInstance($season_id = null) {
    if (!is_numeric($season_id)) {
      throw new MyURYException('Invalid Season ID!', MyURYException::FATAL);
    }
    
    if (!isset(self::$seasons[$season_id])) {
      self::$seasons[$season_id] = new self($season_id);
    }
    
    return self::$season[$season_id];
  }

  private function __construct($season_id) {
    $this->season_id = $season_id;
    self::initDB();

    throw new MyURYException('Not Implemented', MyURYException::FATAL);
  }

  /**
   * Creates a new MyURY Season Application and returns an object representing it
   * @param Array $params An array of Seasons properties compatible with the Models/Scheduler/seasonfrm Form,
   * with a few additional potential customisation options:
   * weeks: An Array of weeks, keyed wk1-10, representing the requested week<br>
   * day: An Array of one or more requested days, 0 being Monday, 6 being Sunday. Corresponds to (s|e)time<br>
   * stime: An Array of sizeof(day) times, represeting the time of day the show should start<br>
   * etime: An Array of sizeof(day) times, represeting the time of day the show should end<br>
   * description: A description of this Season of the Show, in addition to the Show description<br>
   * tags: A string of 0 or more space-seperated tags this Season relates to, in addition to the Show tags<br>
   * show_id: The ID of the Show to assign the application to
   * termid: The ID of the term being applied for. Defaults to the current Term
   * 
   * weeks, day, stime, etime, show_id are all required fields
   * 
   * As this is the initial creation, all tags are <i>approved</i> by the submitted so the show has some initial values
   * 
   * @throws MyURYException
   */
  public static function apply($params = array()) {
    //Validate input
    $required = array('show_id', 'weeks', 'day', 'stime', 'etime');
    foreach ($required as $field) {
      if (!isset($params[$field])) {
        throw new MyURYException('Parameter ' . $field . ' was not provided.', MyURYException::FATAL);
      }
    }

    self::initDB();

    /**
     * @todo Perform required checks:
     * Show ID is a valid Show of the Show Type
     * All keys of weeks between 1 and 10 are defined and boolean (set to false if not defined)
     * All values of day are between 0 and 6
     * All values of stime and etime are between 0 and 86399
     * Select an appropriate value for $term_id
     */
    $term_id = 26;
    
    //Start a transaction
    self::$db->query('BEGIN');
    
    //Right, let's start by getting a Season ID created for this entry
    $season_create_result = self::$db->fetch_column('INSERT INTO schedule.show_season (show_id, termid, submitted, memberid)
      VALUES ($1, $2, $3, $4) RETURNING show_season_id',
            array($params['show_id'], $term_id, CoreUtils::getTimestamp(), User::getInstance()->getID()), true);
    $season_id = $season_create_result[0];
    echo $season_id;
    
    //Now let's allocate store the requested weeks for a term
    for ($i = 1; $i <= 10; $i++) {
      if ($params['weeks']["wk$i"]) {
        self::$db->query('INSERT INTO schedule.show_season_requested_week (show_season_id, week) VALUES ($1, $2)',
                array($season_id, $i), true);
      }
    }
    
    print_r(self::$db->fetch_column('SELECT week FROM schedule.show_season_requested_week WHERE show_season_id=$1',array($season_id)));
    
    //Now for requested times
    for ($i = 0; $i < sizeof($params['day']); $i++) {
      //Deal with the possibility of a show from 11pm to midnight etc.
      if ($params['stime'][$i] < $params['etime'][$i]) {
        $interval = CoreUtils::makeInterval($params['stime'][$i], $params['etime'][$i]);
      } else {
        $interval = CoreUtils::makeInterval($params['etime'][$i], $params['etime'][$i]);
      }
      $start_time = CoreUtils::getTimestamp($params['stime'][$i]);
      //Enter the data
      self::$db->query('INSERT INTO schedule.show_season_requested_time 
        (requested_day, start_time, preference, duration, show_season_id) VALUES ($1, $2, $3, $3, $5)',
              array($params['day'][$i], $start_time, $i, $interval, $season_id));
    }
    
    echo nl2br(print_r(self::$db->fetch_column('SELECT * FROM schedule.show_season_requested_time WHERE show_season_id=$1',array($season_id)),true));
    
    //Actually commit the show to the database!
    self::$db->query('ROLLBACK');
    
    return new self($season_id);
  }
  
  public function getMeta($meta_string) {
    return $this->meta[self::getMetadataKey($meta_string)];
  }
  
  public function getID() {
    return $this->season_id;
  }
  
  public function getShow() {
    return MyURY_Show::getInstance($this->getShowID());
  }
  
  public function getWebpage() {
    return 'http://ury.org.uk/show/'.$this->getShow()->getID().'/'.$this->getID();
  }

}
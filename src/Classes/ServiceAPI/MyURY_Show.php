<?php

/*
 * Provides the Show class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Show class is used to create, view and manupulate Shows within the new MyURY Scheduler Format
 * @version 19122012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * 
 */

class MyURY_Show extends MyURY_Scheduler_Common {

  private static $shows = array();
  private $show_id;
  private $meta;
  private $owner;
  private $credits;
  private $genres;
  private $show_type;
  private $submitted_time;
  private $season_ids;

  public static function getInstance($show_id = null) {
    if (!is_numeric($show_id)) {
      throw new MyURYException('Invalid Show ID!', MyURYException::FATAL);
    }

    if (!isset(self::$shows[$show_id])) {
      self::$shows[$show_id] = new self($show_id);
    }

    return self::$shows[$show_id];
  }

  private function __construct($show_id) {
    $this->show_id = $show_id;
    self::initDB();

    $result = self::$db->fetch_one('SELECT show_type_id, submitted, memberid,
      (SELECT array(SELECT metadata_key_id FROM schedule.show_metadata WHERE show_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, show_metadata_id)) AS metadata_types,
      (SELECT array(SELECT metadata_value FROM schedule.show_metadata WHERE show_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, show_metadata_id)) AS metadata,
      (SELECT array(SELECT credit_type_id FROM schedule.show_credit
         WHERE show_id=$1 AND effective_from <= NOW() AND (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
         ORDER BY show_credit_id)) AS credit_types,
      (SELECT array(SELECT creditid FROM schedule.show_credit
         WHERE show_id=$1 AND effective_from <= NOW() AND (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
         ORDER BY show_credit_id)) AS credits,
      (SELECT array(SELECT genre_id FROM schedule.show_genre
         WHERE show_id=$1 AND effective_from <= NOW() AND (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
         ORDER BY show_genre_id)) AS genres
      FROM schedule.show WHERE show_id=$1', array($show_id));

    //Deal with the easy fields
    $this->owner = (int) $result['memberid'];
    $this->show_type = (int) $result['show_type_id'];
    $this->submitted_time = strtotime($result['submitted']);
    $this->genres = self::$db->decodeArray($result['genres']);

    //Deal with the Credits arrays
    $credit_types = self::$db->decodeArray($result['credit_types']);
    $credits = self::$db->decodeArray($result['credits']);

    for ($i = 0; $i < sizeof($credits); $i++) {
      $this->credits[] = array('type' => $credit_types[$i], 'memberid' => $credits[$i]);
    }


    //Deal with the Metadata arrays
    $metadata_types = self::$db->decodeArray($result['metadata_types']);
    $metadata = self::$db->decodeArray($result['metadata']);

    for ($i = 0; $i < sizeof($metadata); $i++) {
      if (self::isMetadataMultiple($metadata_types[$i])) {
        //Multiples should be an array
        $this->meta[$metadata_types[$i]][] = $metadata[$i];
      } else {
        $this->meta[$metadata_types[$i]] = $metadata[$i];
      }
    }

    //Get information about Seasons
    $this->season_ids = self::$db->fetch_column('SELECT show_season_id
      FROM schedule.show_season WHERE show_id=$1', array($show_id));
  }

  /**
   * Creates a new MyURY Show and returns an object representing it
   * @param Array $params An array of Show properties compatible with the Models/Scheduler/showfrm Form:
   * title: The name of the show<br>
   * description: The description of the show<br>
   * genres: An array of 0 or more genre ids this Show is a member of<br>
   * tags: A string of 0 or more space-seperated tags this Show relates to<br>
   * credits: An array of 1 or more memberids of people related to the Show<br>
   * credittypes: An array of identical size to credits, identifying the type of relation to the Show<br>
   * showtypeid: The ID of the type of show (see schedule.show_type). Defaults to "Show"
   * location: The ID of the location the show will be in
   * 
   * title, description, credits and credittypes are required fields.
   * 
   * As this is the initial creation, all tags are <i>approved</i> by the submitted so the show has some initial values
   * 
   * @todo location (above) Is not in the Show creation form
   * @throws MyURYException
   */
  public static function create($params = array()) {
    //Validate input
    $required = array('title', 'description', 'credits', 'credittypes');
    foreach ($required as $field) {
      if (!isset($params[$field])) {
        throw new MyURYException('Parameter ' . $field . ' was not provided.');
      }
    }

    self::initDB();

    //Get or set the show type id
    if (empty($params['showtypeid'])) {
      $rtype = self::$db->fetch_column('SELECT show_type_id FROM schedule.show_type WHERE name=\'Show\'');
      if (empty($rtype[0])) {
        throw new MyURYException('There is no Show ShowType Available!', MyURYException::FATAL);
      }
      $params['showtypeid'] = (int) $rtype[0];
    }

    if (!isset($params['genres']))
      $params['genres'] = array();
    if (!isset($params['tags']))
      $params['tags'] = '';

    //We're all or nothing from here on out - transaction time
    self::$db->query('BEGIN');

    //Add the basic info, getting the show id

    $result = self::$db->fetch_column('INSERT INTO schedule.show (show_type_id, submitted, memberid)
            VALUES ($1, NOW(), $2) RETURNING show_id', array($params['showtypeid'], $_SESSION['memberid']), true);
    $show_id = $result[0];

    //Right, set the title and description next
    foreach (array('title', 'description') as $key) {
      self::$db->query('INSERT INTO schedule.show_metadata
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)', array(self::getMetadataKey($key), $show_id, $params[$key], $_SESSION['memberid']), true);
    }

    //Genre time powers activate!
    foreach ($params['genres'] as $genre) {
      if (!is_numeric($genre))
        continue;
      self::$db->query('INSERT INTO schedule.show_genre (show_id, genre_id, effective_from, memberid, approvedid)
              VALUES ($1, $2, NOW(), $3, $3)', array($show_id, $genre, $_SESSION['memberid']), true);
    }

    //Explode the tags
    $tags = explode(' ', $params['tags']);
    foreach ($tags as $tag) {
      self::$db->query('INSERT INTO schedule.show_metadata
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)', array(self::getMetadataKey('tag'), $show_id, $tag, $_SESSION['memberid']), true);
    }

    //Set a location
    if (!is_numeric($params['location'])) {
      /**
       * Hardcoded default to Studio 1
       */
      $params['location'] = 1;
    }
    self::$db->query('INSERT INTO schedule.show_location
      (show_id, location_id, effective_from, memberid, approvedid) VALUES ($1, $2, NOW(), $3, $3)', array(
        $show_id, $params['location'], $_SESSION['memberid']
            ), true);

    //And now all that's left is who's on the show
    for ($i = 0; $i < sizeof($params['credits']); $i++) {
      self::$db->query('INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from,
              memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)', array($show_id, (int) $params['credittypes'][$i], $params['credits'][$i], $_SESSION['memberid']), true);
    }

    //Actually commit the show to the database!
    self::$db->query('COMMIT');

    return new self($show_id);
  }

  /**
   * Returns an array of shows which the given user owns or is an active
   * credit in
   * @param int $memberid The ID of the member to check. null means current user.
   * @return Array an array of Show objects attached to the given user
   */
  public static function getShowsAttachedToUser($memberid = null) {
    if ($memberid === null)
      $memberid = $_SESSION['memberid'];
    self::initDB();

    $r = self::$db->fetch_column('SELECT show_id FROM schedule.show WHERE memberid=$1 OR show_id IN
        (SELECT show_id FROM schedule.show_credit WHERE creditid=$1 AND effective_from <= NOW() AND
          (effective_to >= NOW() OR effective_to IS NULL))', array($memberid));

    $return = array();
    foreach ($r as $show_id) {
      $return[] = self::getInstance($show_id);
    }
    return $return;
  }

  public function getMeta($meta_string) {
    return $this->meta[self::getMetadataKey($meta_string)];
  }

  public function getNumberOfSeasons() {
    return sizeof($this->season_ids);
  }

  public function getID() {
    return $this->show_id;
  }

  public function getWebpage() {
    return 'http://ury.org.uk/schedule/shows/' . $this->getID();
  }

  /**
   * Returns an Array of Arrays containing Credit names and roles, or just name.
   * @param boolean $types If true return an array with the role as well. Otherwise just return the credit.
   * @return type
   */
  public function getCreditsNames($types = true) {
    $return = array();
    foreach ($this->credits as $credit) {
      if ($types) {
        $credit['name'] = User::getInstance($credit['memberid'])->getName();
        $credit['type_name'] = self::getCreditName($credit['type']);
      } else {
        $credit = User::getInstance($credit['memberid'])->getName();
      }
      $return[] = $credit;
    }
    return $return;
  }

  public function getCredits() {
    return $this->credits;
  }

  /**
   * @todo Document this method
   * @todo Ajax the All Shows page - this isn't a particularly nice query
   */
  public static function getAllShows($show_type_id = 1) {
    self::initDB();
    self::initCache();
    $key = 'scheduler_all_shows_list';
    
    $r = self::$cache->get($key);
    if ($r !== false) return $r;
    
    $shows = array();
    foreach (self::$db->fetch_column('SELECT show_id FROM schedule.show WHERE show_type_id=$1', array($show_type_id))
    as $show) {
      $shows[] = self::getInstance($show);
    }
    
    self::$cache->set($key, $shows, 3600);

    return $shows;
  }

  public function toDataSource() {
    return array(
        'title' => $this->getMeta('title'),
        'credits' => implode(', ', $this->getCreditsNames(false)),
        'description' => $this->getMeta('description'),
        'seasons' => array(
            'display' => 'text',
            'value' => $this->getNumberOfSeasons(),
            'title' => 'Click to see Seasons for this show',
            'url' => CoreUtils::makeURL('Scheduler', 'listSeasons', array('showid' => $this->getID()))),
        'editlink' => array(
            'display' => 'icon',
            'value' => 'script',
            'title' => 'Edit Show',
            'url' => CoreUtils::makeURL('Scheduler', 'editShow', array('showid' => $this->getID()))),
        'applylink' => array('display' => 'icon',
            'value' => 'calendar',
            'title' => 'Apply for a new Season',
            'url' => CoreUtils::makeURL('Scheduler', 'createSeason', array('showid' => $this->getID()))),
        'micrositelink' => array('display' => 'icon',
            'value' => 'extlink',
            'title' => 'View Show Microsite',
            'url' => $this->getWebpage())
    );
  }

}
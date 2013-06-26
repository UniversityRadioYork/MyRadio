<?php
/**
 * Provides the Show class for MyURY
 * @package MyURY_Scheduler
 */

/**
 * The Show class is used to create, view and manupulate Shows within the new MyURY Scheduler Format
 * @version 05012013
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
  private $credits = array();
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
      $this->credits[] = array('type' => $credit_types[$i], 'memberid' => $credits[$i],
          'User' => User::getInstance($credits[$i]));
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
    return isset($this->meta[self::getMetadataKey($meta_string)]) ?
      $this->meta[self::getMetadataKey($meta_string)] : null;
  }

  public function getNumberOfSeasons() {
    return sizeof($this->season_ids);
  }
  
  public function getAllSeasons() {
    $seasons = array();
    foreach ($this->season_ids as $season_id) {
      $seasons[] = MyURY_Season::getInstance($season_id);
    }
    return $seasons;
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
   * Similar to getCredits, but only returns the User objects. This means the loss of the credit type in the result.
   */
  public function getCreditObjects() {
    $r = array();
    foreach ($this->getCredits() as $credit) {
      $r[] = $credit['User'];
    }
    return $r;
  }
  
  public function isCurrentUserAnOwner() {
    foreach ($this->getCreditObjects() as $user) {
      if ($user->getID() === $_SESSION['memberid']) {
        return true;
      }
    }
    return false;
  }
  
  public function setShowPhoto($tmp_path) {
    $result = self::$db->fetch_column('INSERT INTO schedule.show_image_metadata (memberid, approvedid,
      metadata_key_id, metadata_value, show_id) VALUES ($1, $1, $2, $3, $4) RETURNING show_image_metadata_id',
            array($_SESSION['memberid'], MyURY_Scheduler_Common::getMetadataKey('player_image'), 'tmp', $this->getID()))[0];
    
    $suffix = 'image_meta/ShowImageMetadata/'.$result.'.png';
    $path = Config::$public_media_path.'/'.$suffix;
    move_uploaded_file($tmp_path, $path);
    
    self::$db->query('UPDATE schedule.show_image_metadata SET effective_to=NOW() WHERE metadata_key_id=$1 AND show_id=$2
      AND effective_from IS NOT NULL', array(MyURY_Scheduler_Common::getMetadataKey('player_image'), $this->getID()));
    
    self::$db->query('UPDATE schedule.show_image_metadata SET effective_from=NOW(), metadata_value=$1
      WHERE show_image_metadata_id=$2', array($suffix, $result));
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
  
  /**
   * Find the most messaged shows
   * @param int $date If specified, only messages for timeslots since $date are counted.
   * @return array An array of 30 Shows that have been put through toDataSource, with the addition of a msg_count key,
   * referring to the number of messages sent to that show.
   */
  public static function getMostMessaged($date = 0) {
    $result = self::$db->fetch_all('SELECT show.show_id, count(*) as msg_count FROM sis2.messages
      LEFT JOIN schedule.show_season_timeslot ON messages.timeslotid = show_season_timeslot.show_season_timeslot_id
      LEFT JOIN schedule.show_season ON show_season_timeslot.show_season_id = show_season.show_season_id
      LEFT JOIN schedule.show ON show_season.show_id = show.show_id
      WHERE show_season_timeslot.start_time > $1 GROUP BY show.show_id ORDER BY msg_count DESC LIMIT 30', 
            array(CoreUtils::getTimestamp($date)));
    
    $top = array();
    foreach ($result as $r) {
      $show = self::getInstance($r['show_id'])->toDataSource();
      $show['msg_count'] = intval($r['msg_count']);
      $top[] = $show;
    }
    
    return $top;
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
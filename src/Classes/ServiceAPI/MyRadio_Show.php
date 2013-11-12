<?php
/**
 * Provides the Show class for MyRadio
 * @package MyRadio_Scheduler
 */

/**
 * The Show class is used to create, view and manupulate Shows within the new MyRadio Scheduler Format
 * @version 20130728
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Scheduler
 * @uses \Database
 *
 */

class MyRadio_Show extends MyRadio_Metadata_Common {
  private $show_id;
  private $owner;
  protected $credits = array();
  private $genres;
  private $show_type;
  private $submitted_time;
  private $season_ids;
  private $photo_url;

  protected function __construct($show_id) {
    $this->show_id = $show_id;
    self::initDB();

    $result = self::$db->fetch_one('SELECT show_type_id, submitted, memberid,
      (SELECT array(SELECT metadata_key_id FROM schedule.show_metadata
        WHERE show_id=$1 AND effective_from <= NOW() AND
          (effective_to IS NULL OR effective_to >= NOW())
        ORDER BY effective_from, show_metadata_id)) AS metadata_types,
      (SELECT array(SELECT metadata_value FROM schedule.show_metadata
        WHERE show_id=$1 AND effective_from <= NOW() AND
          (effective_to IS NULL OR effective_to >= NOW())
        ORDER BY effective_from, show_metadata_id)) AS metadata,
      (SELECT array(SELECT metadata_value FROM schedule.show_image_metadata
        WHERE show_id=$1 AND effective_from <= NOW() AND
          (effective_to IS NULL OR effective_to >= NOW())
        ORDER BY effective_from, show_image_metadata_id)) AS image_metadata,
      (SELECT array(SELECT credit_type_id FROM schedule.show_credit
         WHERE show_id=$1 AND effective_from <= NOW() AND
           (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
         ORDER BY show_credit_id)) AS credit_types,
      (SELECT array(SELECT creditid FROM schedule.show_credit
         WHERE show_id=$1 AND effective_from <= NOW() AND
           (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
         ORDER BY show_credit_id)) AS credits,
      (SELECT array(SELECT genre_id FROM schedule.show_genre
         WHERE show_id=$1 AND effective_from <= NOW() AND
           (effective_to IS NULL OR effective_to >= NOW()) AND approvedid IS NOT NULL
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
      if (empty($credits[$i])) {
        continue;
      }
      $this->credits[] = array('type' => (int)$credit_types[$i], 'memberid' => $credits[$i],
          'User' => User::getInstance($credits[$i]));
    }


    //Deal with the Metadata arrays
    $metadata_types = self::$db->decodeArray($result['metadata_types']);
    $metadata = self::$db->decodeArray($result['metadata']);

    for ($i = 0; $i < sizeof($metadata); $i++) {
      if (self::isMetadataMultiple($metadata_types[$i])) {
        //Multiples should be an array
        $this->metadata[$metadata_types[$i]][] = $metadata[$i];
      } else {
        $this->metadata[$metadata_types[$i]] = $metadata[$i];
      }
    }

    //Deal with Show Photo
    /**
     * @todo Support general photo attachment?
     */
    $this->photo_url = Config::$default_person_uri;
    if ($result['image_metadata'] !== '{}') {
      $this->photo_url = Config::$public_media_uri.'/'.self::$db->decodeArray($result['image_metadata'])[0];
    }

    //Get information about Seasons
    $this->season_ids = self::$db->fetch_column('SELECT show_season_id
      FROM schedule.show_season WHERE show_id=$1', array($show_id));
  }

  /**
   * Get the cache key for the Show with this ID.
   * @param int $id
   * @return String
   */
  public static function getCacheKey($id) {
    return 'MyRadio_Show-'.$id;
  }

  /**
   * Creates a new MyRadio Show and returns an object representing it
   * @param Array $params An array of Show properties compatible with the Models/Scheduler/showfrm Form:
   * title: The name of the show<br>
   * description: The description of the show<br>
   * genres: An array of 0 or more genre ids this Show is a member of<br>
   * tags: A string of 0 or more space-seperated tags this Show relates to<br>
   * credits: a 2D Array with keys member and credittype. member is Array of Users, credittype is Array of<br>
   * corresponding credittypeids
   * showtypeid: The ID of the type of show (see schedule.show_type). Defaults to "Show"
   * location: The ID of the location the show will be in
   *
   * title, description, credits and credittypes are required fields.
   *
   * As this is the initial creation, all tags are <i>approved</i> by the submitted so the show has some initial values
   *
   * @todo location (above) Is not in the Show creation form
   * @throws MyRadioException
   */
  public static function create($params = array()) {
    //Validate input
    $required = array('title', 'description', 'credits');
    foreach ($required as $field) {
      if (!isset($params[$field])) {
        throw new MyRadioException('Parameter ' . $field . ' was not provided.');
      }
    }

    self::initDB();

    //Get or set the show type id
    if (empty($params['showtypeid'])) {
      $rtype = self::$db->fetch_column('SELECT show_type_id FROM schedule.show_type WHERE name=\'Show\'');
      if (empty($rtype[0])) {
        throw new MyRadioException('There is no Show ShowType Available!', MyRadioException::FATAL);
      }
      $params['showtypeid'] = (int) $rtype[0];
    }

    if (!isset($params['genres'])) {
      $params['genres'] = array();
    }
    if (!isset($params['tags'])) {
      $params['tags'] = '';
    }

    //We're all or nothing from here on out - transaction time
    self::$db->query('BEGIN');

    //Add the basic info, getting the show id

    $result = self::$db->fetch_column('INSERT INTO schedule.show (show_type_id, submitted, memberid)
            VALUES ($1, NOW(), $2) RETURNING show_id', array($params['showtypeid'], $_SESSION['memberid']), true);
    $show_id = $result[0];

    //Right, set the title and description next
    foreach (array('title', 'description') as $key) {
      self::$db->query('INSERT INTO schedule.show_metadata
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid)
              VALUES ($1, $2, $3, NOW(), $4, $4)',
              array(self::getMetadataKey($key), $show_id, $params[$key], $_SESSION['memberid']), true);
    }

    //Genre time powers activate!
    if (!is_array($params['genres'])) {
      $params['genres'] = array($params['genres']);
    }
    foreach ($params['genres'] as $genre) {
      if (!is_numeric($genre)) {
        continue;
      }
      self::$db->query('INSERT INTO schedule.show_genre (show_id, genre_id, effective_from, memberid, approvedid)
              VALUES ($1, $2, NOW(), $3, $3)', array($show_id, $genre, $_SESSION['memberid']), true);
    }

    //Explode the tags
    $tags = explode(' ', $params['tags']);
    foreach ($tags as $tag) {
      self::$db->query('INSERT INTO schedule.show_metadata
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid)
              VALUES ($1, $2, $3, NOW(), $4, $4)',
              array(self::getMetadataKey('tag'), $show_id, $tag, $_SESSION['memberid']), true);
    }

    //Set a location
    if (empty($params['location'])) {
      /**
       * Hardcoded default to Studio 1
       * @todo Location support
       */
      $params['location'] = 1;
    }
    self::$db->query('INSERT INTO schedule.show_location
      (show_id, location_id, effective_from, memberid, approvedid) VALUES ($1, $2, NOW(), $3, $3)', array(
        $show_id, $params['location'], $_SESSION['memberid']
            ), true);

    //And now all that's left is who's on the show
    for ($i = 0; $i < sizeof($params['credits']['member']); $i++) {
      //Skip blank entries
      if (empty($params['credits']['member'][$i])) {
        continue;
      }
      self::$db->query('INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from,
              memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
        array($show_id, (int) $params['credits']['credittype'][$i],$params['credits']['member'][$i]->getID(), $_SESSION['memberid']), true);
    }

    //Actually commit the show to the database!
    self::$db->query('COMMIT');

    return new self($show_id);
  }

  public function getNumberOfSeasons() {
    return sizeof($this->season_ids);
  }

  public function getAllSeasons() {
    $seasons = array();
    foreach ($this->season_ids as $season_id) {
      $seasons[] = MyRadio_Season::getInstance($season_id);
    }
    return $seasons;
  }

  /**
   * Internally associates a Season with this Show.
   * Does not persist in database. Used for updating the cache.
   * @param int $id
   */
  public function addSeason($id) {
    $this->season_ids[] = $id;
    $this->updateCacheObject();
  }

  public function getID() {
    return $this->show_id;
  }

  public function getWebpage() {
    return '//ury.org.uk/schedule/shows/' . $this->getID();
  }

  /**
   * Get the web url for the Show Photo
   * @return String
   */
  public function getShowPhoto() {
    return $this->photo_url;
  }

  /**
   * Returns the ID for the type of Show
   * @return int
   */
  public function getShowType() {
    return $this->show_type;
  }

  /**
   * Return the primary Genre. Shows generally only have one anyway.
   */
  public function getGenre() {
    return $this->genres[0];
  }

  public function isCurrentUserAnOwner() {
    if ($this->owner === $_SESSION['memberid']) {
      return true;
    }
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
            array($_SESSION['memberid'], self::getMetadataKey('player_image'), 'tmp', $this->getID()))[0];

    $suffix = 'image_meta/ShowImageMetadata/'.$result.'.png';
    $path = Config::$public_media_path.'/'.$suffix;
    move_uploaded_file($tmp_path, $path);

    self::$db->query('UPDATE schedule.show_image_metadata SET effective_to=NOW() WHERE metadata_key_id=$1 AND show_id=$2
      AND effective_from IS NOT NULL', array(self::getMetadataKey('player_image'), $this->getID()));

    self::$db->query('UPDATE schedule.show_image_metadata SET effective_from=NOW(), metadata_value=$1
      WHERE show_image_metadata_id=$2', array($suffix, $result));
  }

  /**
   * Sets a metadata key to the specified value.
   *
   * If any value is the same as an existing one, no action will be taken.
   * If the given key has is_multiple, then the value will be added as a new, additional key.
   * If the key does not have is_multiple, then any existing values will have effective_to
   * set to the effective_from of this value, effectively replacing the existing value.
   * This will *not* unset is_multiple values that are not in the new set.
   *
   * @param String $string_key The metadata key
   * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
   * for value in the array.
   * @param int $effective_from UTC Time the metavalue is effective from. Default now.
   * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
   * @param null $table Used for compatibility with parent.
   * @param null $pkey Used for compatibility with parent.
   */
  public function setMeta($string_key, $value, $effective_from = null, $effective_to = null,
          $table = null, $pkey = null) {
   $r = parent::setMeta($string_key, $value, $effective_from, $effective_to,
           'schedule.show_metadata', 'show_id');
   $this->updateCacheObject();
   return $r;
  }

  /**
   * Sets the Genre, if it hasn't changed
   * @param int $genreid
   */
  public function setGenre($genreid) {
    if (empty($genreid)) {
      throw new MyRadioException('Genre cannot be empty!', 400);
    }
    if ($genreid != $this->getGenre()) {
      self::$db->query('UPDATE schedule.show_genre SET effective_to=NOW() WHERE show_id=$1',
              array($this->getID()));
      self::$db->query('INSERT INTO schedule.show_genre (show_id, genre_id, effective_from, memberid, approvedid)
              VALUES ($1, $2, NOW(), $3, $3)', array($this->getID(), $genreid, User::getInstance()->getID()));
      $this->genres = [$genreid];
      $this->updateCacheObject();
    }
  }

  /**
   * Updates the list of Credits.
   *
   * Existing credits are kept active, ones that are not in the new list are set to effective_to now,
   * and ones that are in the new list but not exist are created with effective_from now.
   *
   * @param User[] $users An array of Users associated.
   * @param int[] $credittypes The relevant credittypeid for each User.
   */
  public function setCredits($users, $credittypes, $table = null, $pkey = null) {
    $r = parent::setCredits($users, $credittypes, 'schedule.show_credit', 'show_id');
    $this->updateCacheObject();
    return $r;
  }

  /**
   * @todo Document this method
   * @todo Ajax the All Shows page - this isn't a particularly nice query
   */
  public static function getAllShows($show_type_id = 1) {
    $show_ids = self::$db->fetch_column(
      'SELECT show_id FROM schedule.show '
      . 'WHERE show_type_id=$1 '
      . 'ORDER BY ('
      . '  SELECT metadata_value FROM schedule.show_metadata '
      . '  WHERE show_id=show_id AND metadata_key_id=2 '
      . '  AND effective_from <= NOW() '
      . '  AND (effective_to IS NULL OR effective_to > NOW()) '
      . '  ORDER BY effective_from DESC LIMIT 1'
      . ');',
      [$show_type_id]
    );
    return array_map(
      function($show_id) { return self::getInstance($show_id); },
      array_values($show_ids)
    );
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

  /**
   * Returns the current Show on air, if there is one.
   * @param int $time Optional integer timestamp
   *
   * @return MyRadio_Show|null
   */
  public static function getCurrentShow($time = null) {
    $timeslot = MyRadio_Timeslot::getCurrentTimeslot($time);
    if (empty($timeslot)) {
      return null;
    } else {
      return $timeslot->getSeason()->getShow();
    }
  }

  /**
   * Find the most listened Shows
   * @param int $date If specified, only messages for timeslots since $date are counted.
   * @return array An array of 30 Timeslots that have been put through toDataSource, with the addition of a msg_count key,
   * referring to the number of messages sent to that show.
   */
  public static function getMostListened($date = 0) {
    $key = 'stats_show_mostlistened';
    if (($top = self::$cache->get($key)) !== false) {
      return $top;
    }

    $result = self::$db->fetch_all('SELECT show_id, SUM(listeners) AS listeners_sum FROM (SELECT show_season_id,
      (SELECT COUNT(*) FROM strm_log
        WHERE (starttime < show_season_timeslot.start_time AND endtime >= show_season_timeslot.start_time)
        OR (starttime >= show_season_timeslot.start_time
            AND starttime < show_season_timeslot.start_time + show_season_timeslot.duration)) AS listeners
        FROM schedule.show_season_timeslot
        WHERE start_time > $1) AS t1 LEFT JOIN schedule.show_season ON t1.show_season_id = show_season. show_season_id
        GROUP BY show_id ORDER BY listeners_sum DESC LIMIT 30',
            array(CoreUtils::getTimestamp($date)));

    $top = array();
    foreach ($result as $r) {
      $show = self::getInstance($r['show_id'])->toDataSource();
      $show['listeners'] = intval($r['listeners_sum']);
      $top[] = $show;
    }

    self::$cache->set($key, $top, 86400);
    return $top;
  }

  public function toDataSource($full = true) {
    $data = array(
        'show_id' => $this->getID(),
        'title' => $this->getMeta('title'),
        'credits' => implode(', ', $this->getCreditsNames(false)),
        'description' => $this->getMeta('description'),
        'show_type_id' => $this->show_type,
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
            'url' => $this->getWebpage()),
        'photo' => $this->getShowPhoto()
    );

    if ($full) {
      $data['credits'] = array_map(function($x) {
        $x['User'] = $x['User']->toDataSource(false);
        return $x;
      }, $this->getCredits());
    }

    return $data;
  }

}

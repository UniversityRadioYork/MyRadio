<?php

/*
 * Provides the Show class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Show class is used to create, view and manupulate Shows within the new MyURY Scheduler Format
 * @version 12082012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * 
 */

class MyURY_Show extends ServiceAPI {

  private static $shows = array();
  private static $metadata_keys = array();
  private $show_id;
  private $meta;
  private $owner;
  private $credits;
  private $genres;
  private $show_type;
  private $submitted_time;

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
    
    echo nl2br(print_r($result,true));
    
    //Deal with the easy fields
    $this->owner = (int)$result['memberid'];
    $this->show_type = (int)$result['show_type_id'];
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
      $this->meta[$metadata_types[$i]] = $metadata[$i];
    }
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
   * 
   * title, description, credits and credittypes are required fields.
   * 
   * As this is the initial creation, all tags are <i>approved</i> by the submitted so the show has some initial values
   * 
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
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
              array(self::getMetadataKey($key), $show_id, $params[$key], $_SESSION['memberid']), true);
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
              (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
              array(self::getMetadataKey('tag'), $show_id, $tag, $_SESSION['memberid']), true);
    }

    //And now all that's left is who's on the show
    for ($i = 0; $i < sizeof($params['credits']); $i++) {
      self::$db->query('INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from,
              memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
              array($show_id, (int) $params['credittypes'][$i], $params['credits'][$i], $_SESSION['memberid']), true);
    }

    echo nl2br(print_r(new self($show_id), true));

    //I'm still testing. Don't commit yet.
    self::$db->query('ROLLBACK');
  }

  /**
   * Gets the id for the string representation of a type of metadata
   */
  private static function getMetadataKey($string) {
    if (empty(self::$metadata_keys)) {
      self::initDB();
      $r = self::$db->fetch_all('SELECT * FROM schedule.metadata_key');
      foreach ($r as $key) {
        self::$metadata_keys[$key['name']] = $key['metadata_key_id'];
      }
    }
    if (!isset(self::$metadata_keys[$string])) {
      throw new MyURYException('Metadata Key ' . $string . ' does not exist');
    }
    return self::$metadata_keys[$string];
  }

}
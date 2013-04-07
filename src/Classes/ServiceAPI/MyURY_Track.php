<?php
/**
 * This file provides the MyURY_Track class for MyURY
 * @package MyURY_Core
 */

/**
 * The MyURY_Track class provides and stores information about a Track
 * 
 * @version 06042013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @todo Cache this
 */
class MyURY_Track extends ServiceAPI {
  /**
   * The Singleton store for Track objects
   * @var MyURY_Track
   */
  private static $tracks = array();
  /**
   * The number of the Track on a Record
   * @var int
   */
  private $number;
  /**
   * The title of the Track
   * @var String
   */
  private $title;
  /**
   * The Artist of the Track
   * @var int
   */
  private $artist;
  /**
   * The length of the Track, in seconds
   * @var int
   */
  private $length;
  /**
   * The genreid of the Track
   * @var char
   */
  private $genre;
  /**
   * How long the intro (non-vocal) part of the track is, in seconds
   * @var int
   */
  private $intro;
  /**
   * Whether the track is clean:<br>
   * y: The track is verified as clean<br>
   * n: The track is verified as unclean<br>
   * u: This track has not been checked for cleanliness
   * @var String
   */
  private $clean;
  /**
   * The Unique ID of this Track
   * @var int
   */
  private $trackid;
  /**
   * The Record this track belongs to
   * @var int
   */
  private $record;
  /**
   * Whether or not there is a digital version of this track stored in the Central Database
   * @var bool
   */
  private $digitised;
  /**
   * The member who digitised this track
   * @var User
   */
  private $digitisedby;
  
  /**
   * Initiates the Track variables
   * @param int $trackid The ID of the track to initialise
   * @todo Genre class
   * @todo Artist normalisation
   */
  private function __construct($trackid) {
    $this->trackid = $trackid;
    $result = self::$db->fetch_one('SELECT * FROM public.rec_track WHERE trackid=$1 LIMIT 1', array($trackid));
    if (empty($result)) {
      throw new MyURYException('The specified Track does not seem to exist');
      return;
    }
    
    $this->artist = $result['artist'];
    $this->clean = $result['clean'];
    $this->digitised = ($result['digitised'] == 't') ? true : false;
    $this->digitisedby = empty($result['digitisedby']) ? null : User::getInstance($result['digitisedby']);
    $this->genre = $result['genre'];
    $this->intro = strtotime('1970-01-01 '.$result['intro'].'+00');
    $this->length = strtotime('1970-01-01 '.$result['length'].'+00');
    $this->number = (int)$result['intro'];
    $this->record = Album::getInstance($result['recordid']);
    $this->title = $result['title'];
  }
  
  /**
   * Returns the current instance of that Track object if there is one, or runs the constructor if there isn't
   * @param int $trackid The ID of the Track to return an object for
   */
  public static function getInstance($trackid = -1) {
    self::__wakeup();
    if (!is_numeric($trackid)) {
      throw new MyURYException('Invalid Track ID!', MyURYException::FATAL);
    }

    if (!isset(self::$tracks[$trackid])) {
      self::$tracks[$trackid] = new self($trackid);
    }

    return self::$tracks[$trackid];
  }
  
  /**
   * Returns a "summary" string - the title and artist seperated with a dash.
   * @return String
   */
  public function getSummary() {
    return $this->getTitle() . ' - '.$this->getArtist();
  }
  
  /**
   * Get the Title of the Track
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Get the Artist of the Track
   * @return String
   */
  public function getArtist() {
    return $this->artist;
  }
  
  /**
   * Get the Album of the Track;
   * @return Album
   */
  public function getAlbum() {
    return $this->record;
  }
  
  /**
   * Get the unique trackid of the Track
   * @return int
   */
  public function getID() {
    return $this->trackid;
  }
  
  /**
   * Get the length of the Track, in seconds
   * @return int
   */
  public function getLength() {
    return $this->length;
  }
  
  /**
   * Get whether or not the track is digitised
   * @return bool
   */
  public function getDigitised() {
    return $this->digitised;
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'summary' => $this->getSummary(), //Used for legacy parts of the NIPSWeb Client
        'title' => $this->getTitle(),
        'artist' => $this->getArtist(),
        'type' => 'central', //Tells NIPSWeb Client what this item type is
        'album' => $this->getAlbum()->toDataSource(),
        'trackid' => $this->getID(),
        'length' => CoreUtils::happyTime($this->getLength(), true, false),
        'clean' => $this->clean === 'c',
        'digitised' => $this->getDigitised()
    );
  }
  
  /**
   * Returns an Array of Tracks matching the given partial title
   * @param String $title A partial or total title to search for
   * @param String $artist a partial or total title to search for
   * @param int $limit The maximum number of tracks to return
   * @param bool $digitised Whether the track must be digitised. Default false.
   * @return Array of Track objects
   */
  private static function findByNameArtist($title, $artist, $limit, $digitised = false) {
    $result = self::$db->fetch_column('SELECT trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title ILIKE \'%\' || $1 || \'%\'
      AND rec_track.artist ILIKE \'%\' || $2 || \'%\'
      '.($digitised ? ' AND digitised=\'t\'' : '').'
      LIMIT $3',
            array($title, $artist, $limit));
    
    $response = array();
    foreach ($result as $trackid) {
      $response[] = new MyURY_Track($trackid);
    }
    
    return $response;
  }
  
  /**
   * 
   * @param Array $options One or more of the following:
   * title: String title of the track
   * artist: String artist name of the track
   * digitised: Boolean whether or not digitised
   * itonesplaylistid: Tracks that are members of the iTones_Playlist id
   * limit: Maximum number of items to return. 0 = No Limit
   */
  public static function findByOptions($options) {
    if (!isset($options['title'])) $options['title'] = '';
    if (!isset($options['artist'])) $options['artist'] = '';
    if (!isset($options['digitised'])) $options['digitised'] = true;
    if (!isset($options['itonesplaylistid'])) $options['itonesplaylistid'] = null;
    if (!isset($options['limit'])) $options['limit'] = Config::$ajax_limit_default;
    
    //Prepare paramaters
    $sql_params = array($options['title'], $options['artist']);
    if ($options['limit'] != 0) $sql_params[] = $options['limit'];
    
    //Do the bulk of the sorting with SQL
    $result = self::$db->fetch_column('SELECT trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title ILIKE \'%\' || $1 || \'%\'
      AND rec_track.artist ILIKE \'%\' || $2 || \'%\'
      '.($options['digitised'] ? ' AND digitised=\'t\'' : '').'
      '.($options['limit'] == 0 ? '' : ' LIMIT $3'),
            $sql_params);
    
    $response = array();
    foreach ($result as $trackid) {
      $response[] = new MyURY_Track($trackid);
    }
    
    //Intersect with iTones if necessary, then return
    return empty($options['itonesplaylistid']) ? $response : array_intersect($response,
      iTones_Playlist::getInstance($options['itonesplaylistid'])->getTracks());
  }
}

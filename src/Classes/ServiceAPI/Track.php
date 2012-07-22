<?php
/**
 * This file provides the Track class for MyURY
 * @package MyURY_Core
 */

/**
 * The Track class provides and stores information about a Track
 * 
 * @version 25062012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @todo Write this
 */
class Track extends ServiceAPI {
  /**
   * The Singleton store for Track objects
   * @var Track
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
   * @var int
   */
  private $genre;
  /**
   * The name of the genre of the Track
   * @var String
   */
  private $genre_name;
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
   * The Unique ID of the Record this track is in
   * @var int
   */
  private $recordid;
  /**
   * Whether or not there is a digital version of this track stored in the Central Database
   * @var bool
   */
  private $digitised;
  /**
   * The ID of the member who digitised this track
   * @var int
   */
  private $digitisedby;
  
  /**
   * Initiates the Track variables
   * @param int $trackid The ID of the track to initialise
   */
  private function __construct($trackid) {
    $this->trackid = $trackid;
    throw new MyURYException('Not implemented Track::__construct');
  }
  
  /**
   * Returns the current instance of that Track object if there is one, or runs the constructor if there isn't
   * @param int $trackid The ID of the Track to return an object for
   * @throws MyURYException Throws an exception because it is not implemented
   */
  public static function getInstance($trackid = -1) {
    self::__wakeup();
    throw new MyURYException('Not implemented Track::getInstance');
  }
  
  /**
   * Returns an Array of Tracks matching the given partial title
   * @param String $title A partial or total title to search for
   * @param int $limit The maximum number of tracks to return
   * @return Array 2D with each first dimension an Array as follows:<br>
   * title: The title of the track<br>
   * artist: The artist of the track (String name)<br>
   * record: The name of the record the track is in<br>
   * trackid: The unique id of the track
   */
  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT rec_track.title, rec_track.artist, rec_record.title AS record, trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            array($title, $limit));
  }
}

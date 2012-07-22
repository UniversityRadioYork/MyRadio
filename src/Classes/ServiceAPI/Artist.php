<?php
/**
 * This file provides the Artist class for MyURY
 * @package MyURY_Core
 */

/**
 * The Artist class provides and stores information about a Artist
 * 
 * @version 27062012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @todo The completion of this module is impossible as Artists do not have
 * unique identifiers. For this to happen, BAPS needs to be replaced/updated
 * @package MyURY_Core
 * @uses Database
 */
class Artist extends ServiceAPI {
  /**
   * When above @todo is completed, this will store cached artist singletons
   * @var Artist 
   */
  private static $artists = array();
  
  /**
   * Initiates the Artist object
   * @param int $artistid The ID of the Artist to initialise
   */
  private function __construct($artistid) {
    $this->artistid = $artistid;
    throw new MyURYException('Not implemented ARtist::__construct');
  }
  
  public static function getInstance($trackid = -1) {
    self::__wakeup();
    throw new MyURYException('Not implemented ARtist::getInstance');
  }
  
  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT DISTINCT rec_track.artist AS title, 0 AS artistid
      FROM rec_track WHERE rec_track.artist ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            array($title, $limit));
  }
}

<?php
/**
 * The Artist class provides and stores information about a Artist
 * @version 27062012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @todo The completion of this module is impossible as Artists do not have
 * unique identifiers. For this to happen, BAPS needs to be replaced/updated
 */
class Artist extends ServiceAPI {
  private static $artists = array();
  
  /**
   * Initiates the Track variables
   * @param int $trackid The ID of the track to initialise
   */
  private function __construct($trackid) {
    $this->artistid = $artistid;
    throw new MyURYException('Not implemented Track::__construct');
  }
  
  public static function getInstance($trackid = -1) {
    self::__wakeup();
    throw new MyURYException('Not implemented Track::getInstance');
  }
  
  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT DISTINCT rec_track.artist AS title, 0 AS artistid
      FROM rec_track WHERE rec_track.artist ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            array($title, $limit));
  }
}

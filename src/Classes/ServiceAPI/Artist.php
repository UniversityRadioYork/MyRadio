<?php
/**
 * This file provides the Artist class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The Artist class provides and stores information about a Artist
 * 
 * @version 20130605
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @todo The completion of this module is impossible as Artists do not have
 * unique identifiers. For this to happen, BAPS needs to be replaced/updated
 * @package MyRadio_Core
 * @uses \Database
 */
class Artist extends ServiceAPI {
  
  /**
   * Initiates the Artist object
   * @param int $artistid The ID of the Artist to initialise
   */
  protected function __construct($artistid) {
    $this->artistid = $artistid;
    throw new MyRadioException('Not implemented Artist::__construct');
  }
  
  /**
   * Returns an Array of Artists matching the given partial name
   * @param String $title A partial or total title to search for
   * @param int $limit The maximum number of tracks to return
   * @return Array 2D with each first dimension an Array as follows:<br>
   * title: The name of the artist<br>
   * artistid: Always 0 until Artist support is implemented
   */
  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT DISTINCT rec_track.artist AS title, 0 AS artistid
      FROM rec_track WHERE rec_track.artist ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            array($title, $limit));
  }
}

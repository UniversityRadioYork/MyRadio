<?php
/**
 * The Track class provides and stores information about a Track
 * @version 25062012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 */
class Track extends ServiceAPI {
  private static $tracks = array();
  private $number;
  private $title;
  private $artist;
  private $length;
  private $genre;
  private $genre_name;
  private $intro;
  private $clean;
  private $trackid;
  private $recordid;
  private $digitised;
  private $digitisedby;
  
  /**
   * Initiates the Track variables
   * @param int $trackid The ID of the track to initialise
   */
  private function __construct($trackid) {
    $this->trackid = $trackid;
    throw new MyURYException('Not implemented Track::__construct');
  }
  
  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT rec_track.title, rec_track.artist, rec_record.title AS record, trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            array($title, $limit));
  }
}

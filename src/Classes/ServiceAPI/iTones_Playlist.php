<?php
/**
 * This file provides the iTones_Playlist class for MyURY - Contains a predefined list of Central tracks
 * @package MyURY_iTones
 */

/**
 * The iTones_Playlist class helps provide control and access to managed playlists
 * 
 * @version 20130709
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_iTones
 * @uses \Database
 */
class iTones_Playlist extends ServiceAPI {
  /**
   * The Singleton store for AudioResource objects
   * @var MyURY_Track
   */
  private static $playlists = array();
  
  private $playlistid;
  
  private $title;
  
  private $image;
  
  private $description;
  
  private $lock;
  
  private $locktime;
  
  private $tracks = array();
  
  private $weight = 0;
  
  /**
   * Initiates the ManagedPlaylist variables
   * @param int $playlistid The ID of the managed playlist to initialise
   * Note: Only links *non-expired* items
   */
  private function __construct($playlistid) {
    $this->playlistid = $playlistid;
    $result = self::$db->fetch_one('SELECT * FROM jukebox.playlists WHERE playlistid=$1 LIMIT 1',
            array($playlistid));
    if (empty($result)) {
      throw new MyURYException('The specified iTones Playlist does not seem to exist');
      return;
    }
    
    $this->title = $result['title'];
    $this->image = $result['image'];
    $this->description = $result['description'];
    $this->lock = User::getInstance($result['lock']);
    $this->locktime = (int)$result['locktime'];
    $this->weight = (int)$result['weight'];
    
    $items = self::$db->fetch_column('SELECT trackid FROM jukebox.playlist_entries WHERE playlistid=$1
      AND revision_removed IS NULL
      ORDER BY entryid', array($this->playlistid));
    
    foreach ($items as $id) {
      $this->tracks[] = MyURY_Track::getInstance($id);
    }
  }
  
  /**
   * Returns the current instance of that Playlist object if there is one, or runs the constructor if there isn't
   * @param String $resid The ID of the Playlist to return an object for
   */
  public static function getInstance($resid = -1) {
    self::__wakeup();
    if (!is_string($resid) or empty($resid)) {
      throw new MyURYException('Invalid iTonesPlaylistID!');
    }

    if (!isset(self::$playlists[$resid])) {
      self::$playlists[$resid] = new self($resid);
    }

    return self::$playlists[$resid];
  }
  
  /**
   * Return the MyURY_Tracks that belong to this playlist
   * @return Array of MyURY_Track objects
   */
  public function getTracks() {
    return $this->tracks;
  }
  
  /**
   * Get the Title of the Playlist
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Get the unique playlistid of the Playlist
   * @return String
   */
  public function getID() {
    return $this->playlistid;
  }
  
  /**
   * Get the long description of the Playlist
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }
  
  /**
   * Get the jukebox weight of the Playlist
   * @return int
   */
  public function getWeight() {
    return $this->weight;
  }
  
  /**
   * Get an array of all Playlists
   * @return Array of iTones_Playlist objects
   */
  public static function getAlliTonesPlaylists() {
    self::__wakeup();
    $result = self::$db->fetch_column('SELECT playlistid FROM jukebox.playlists ORDER BY title');
    
    return self::resultSetToObjArray($result);
  }
  
  public static function getPlaylistsWithTrack(MyURY_Track $track) {
    $result = self::$db->fetch_column('SELECT playlistid FROM jukebox.playlist_entries WHERE trackid=$1
      AND revision_removed IS NULL', array($track->getID()));
    
    return self::resultSetToObjArray($result);
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'title' => $this->getTitle(),
        'playlistid' => $this->getID(),
        'description' => $this->getDescription(),
        'tracks' => $this->getTracks()
    );
  }
}

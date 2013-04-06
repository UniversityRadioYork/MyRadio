<?php
/**
 * This file provides the NIPSWeb_ManagedPlaylist class for MyURY - Contains Jingles etc.
 * ilk
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_ManagedPlaylist class helps provide control and access to managed playlists
 * 
 * @version 15032013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_ManagedPlaylist extends ServiceAPI {
  /**
   * The Singleton store for AudioResource objects
   * @var Track
   */
  private static $playlists = array();
  
  private $managed_playlist_id;
  
  private $items;
  
  private $name;
  
  private $folder;
  
  private $item_ttl;
  
  /**
   * Initiates the ManagedPlaylist variables
   * @param int $playlistid The ID of the managed playlist to initialise
   * @todo Items
   */
  private function __construct($playlistid) {
    $this->managed_playlist_id = $playlistid;
    $result = self::$db->fetch_one('SELECT * FROM bapsplanner.managed_playlists WHERE managedplaylistid=$1 LIMIT 1',
            array($playlistid));
    if (empty($result)) {
      throw new MyURYException('The specified NIPSWeb Managed Playlist does not seem to exist');
      return;
    }
    
    $this->name = $result['name'];
    $this->folder = $result['folder'];
    $this->item_ttl = $result['item_ttl'];
  }
  
  /**
   * Returns the current instance of that ManagedItem object if there is one, or runs the constructor if there isn't
   * @param int $resid The ID of the ManagedItem to return an object for
   */
  public static function getInstance($resid = -1) {
    self::__wakeup();
    if (!is_numeric($resid)) {
      throw new MyURYException('Invalid ManagedPlaylistID!');
    }

    if (!isset(self::$playlists[$resid])) {
      self::$playlists[$resid] = new self($resid);
    }

    return self::$playlists[$resid];
  }
  
  /**
   * Get the Title of the ManagedPlaylist
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Get the unique manageditemid of the ManagedPlaylist
   * @return int
   */
  public function getID() {
    return $this->managed_playlist_id;
  }
  
  /**
   * Get the unique path of the ManagedPlaylist
   * @return String
   */
  public function getFolder() {
    return $this->folder;
  }
  
  /**
   * Get the length of the ManagedItem, in seconds
   * @todo Not Implemented as Length not stored in DB
   * @return int
   */
  public function getLength() {
    return $this->length;
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'type' => 'aux', //Legacy NIPSWeb Views
        'summary' => $this->getTitle(), //Again, freaking NIPSWeb
        'title' => $this->getTitle(),
        'managedid' => $this->getID(),
        'length' => $this->getLength(),
        'trackid' => $this->getID(),
        'recordid' => 'ManagedDB', //Legacy NIPSWeb Views
        'auxid' => 'managed:' . $this->getID() //Legacy NIPSWeb Views
    );
  }
}

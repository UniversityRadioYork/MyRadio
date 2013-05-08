<?php
/**
 * This file provides the NIPSWeb_AutoPlaylist class for MyURY - Contains Jingles etc.
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_AutoPlaylist class helps provide control and access to Auto playlists
 * 
 * @version 20130508
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_AutoPlaylist extends ServiceAPI {
  /**
   * The Singleton store for AutoPlaylist objects
   * @var NIPSWeb_AutoPlaylist
   */
  private static $playlists = array();

  private $auto_playlist_id;

  protected $name;

  protected $items;

  protected $query;


  /**
   * Initiates the AutoPlaylist variables
   * @param int $playlistid The ID of the auto playlist to initialise
   */
  private function __construct($playlistid) {
    $this->auto_playlist_id = $playlistid;
    $result = self::$db->fetch_one('SELECT * FROM bapsplanner.auto_playlists WHERE auto_playlist_id=$1 LIMIT 1',
            array($playlistid));
    if (empty($result)) {
      throw new MyURYException('The specified NIPSWeb Auto Playlist does not seem to exist');
      return;
    }
    
    $this->name = $result['name'];
    $this->query = $result['query'];

    $items = self::$db->fetch_all($this->query);
    $this->items = array();

    foreach ($items['trackid'] as $id) {
      $this->tracks[] = MyURY_Track::getInstance($id);
    }
  }


  /**
   * Returns the current instance of that AutoPlaylist object if there is one, or runs the constructor if there isn't
   * @param int $resid The ID of the AutoPlaylist to return an object for
   */
  public static function getInstance($resid = -1) {
    self::__wakeup();
    if (!is_numeric($resid)) {
      throw new MyURYException('Invalid AutoPlaylistID!');
    }

    if (!isset(self::$playlists[$resid])) {
      self::$playlists[$resid] = new self($resid);
    }

    return self::$playlists[$resid];
  }

  /**
   * Return the NIPSWeb_ManagedItems that belong to this playlist
   * @return Array of ManagedItems
   */
  public function getItems() {
    return $this->items;
  }
  
  /**
   * Get the Title of the AutoPlaylist
   * @return String
   */
  public function getTitle() {
    return $this->name;
  }
  
  /**
   * Get the unique manageditemid of the AutoPlaylist
   * @return int
   */
  public function getID() {
    return $this->auto_playlist_id;
  }

  public static function getAllAutoPlaylists($editable_only = false) {
    if ($editable_only && !User::getInstance()->hasAuth(AUTH_EDITCENTRALRES)) return array();
    $result = self::$db->fetch_column('SELECT auto_playlist_id FROM bapsplanner.auto_playlists ORDER BY name');
    $response = array();
    foreach ($result as $id) {
      $response[] = self::getInstance($id);
    }
    
    return $response;
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
    );
  }
}
<?php

/**
 * This file provides the NIPSWeb_AutoPlaylist class for MyRadio - Contains Jingles etc.
 * @package MyRadio_NIPSWeb
 */

/**
 * The NIPSWeb_AutoPlaylist class helps provide control and access to Auto playlists
 * 
 * @version 20130508
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_NIPSWeb
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
  protected $tracks;
  protected $query;

  /**
   * Initiates the AutoPlaylist variables
   * @param int $playlistid The ID of the auto playlist to initialise
   */
  protected function __construct($playlistid) {
    $this->auto_playlist_id = $playlistid;
    $result = self::$db->fetch_one('SELECT * FROM bapsplanner.auto_playlists WHERE auto_playlist_id=$1 LIMIT 1', array($playlistid));
    if (empty($result)) {
      throw new MyRadioException('The specified NIPSWeb Auto Playlist does not seem to exist');
      return;
    }

    $this->name = $result['name'];
    $this->query = $result['query'];
  }

  /**
   * Return the MyRadio_Tracks that belong to this playlist
   * 
   * Lazily evaluated - Tracks will not be loaded until the method is called
   * 
   * @return Array[MyRadio_Track]
   */
  public function getTracks() {
    if (empty($this->tracks)) {
      $tracks = self::$db->fetch_all($this->query);
      $this->tracks = array();

      foreach ($tracks as $id) {
        $this->tracks[] = MyRadio_Track::getInstance($id['trackid']);
      }
    }
    return $this->tracks;
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
    if ($editable_only && !MyRadio_User::getInstance()->hasAuth(AUTH_EDITCENTRALRES))
      return array();
    $result = self::$db->fetch_column('SELECT auto_playlist_id FROM bapsplanner.auto_playlists ORDER BY name');
    $response = array();
    foreach ($result as $id) {
      $response[] = self::getInstance($id);
    }

    return $response;
  }

  public static function findByName($name) {
    $result = self::$db->fetch_column('SELECT auto_playlist_id FROM bapsplanner.auto_playlists WHERE name=$1', array($name));

    if (empty($result))
      throw new MyRadioException('That auto playlist does not exist!');
    else
      return self::getInstance($result[0]);
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
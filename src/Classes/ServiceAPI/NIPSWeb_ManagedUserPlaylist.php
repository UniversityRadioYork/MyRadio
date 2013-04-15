<?php
/**
 * This file provides the NIPSWeb_ManagedUserPlaylist class for MyURY - Contains My Jingles and My Beds
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_ManagedUserPlaylist class provide My Jingles and My Beds for users.
 * 
 * @version 15042013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_ManagedUserPlaylist extends NIPSWeb_ManagedPlaylist {
  
  /**
   * Initiates the UserPlaylist variables
   * @param int $playlistid The folder of the user playlist to initialise, e.g. 7449/beds
   * Note: Only links *non-expired* items
   */
  private function __construct($playlistid) {
    $this->folder = $playlistid;
    
    $this->name = self::getNameFromFolder($this->folder);
    
    $items = self::$db->fetch_column('SELECT manageditemid FROM bapsplanner.managed_user_items
      WHERE managedplaylistid=$1
        AND (expirydate IS NULL OR expirydate > NOW())
        ORDER BY title', array('membersmusic/'.$this->managed_playlist_id));
    $this->items = array();
    foreach ($items as $id) {
      /**
       * Pass this to the ManagedItem - it's called Dependency Injection and prevents loops and looks pretty
       * http://stackoverflow.com/questions/4903387/can-2-singleton-classes-reference-each-other
       * http://www.phparch.com/2010/03/static-methods-vs-singletons-choose-neither/
       */
      $this->items[] = NIPSWeb_ManagedItem::getInstance((int)$id, $this);
    }
  }
  
  /**
   * Returns the current instance of that ManagedUserPlaylist object if there is one, or runs the constructor if there isn't
   * @param String $resid The String ID of the ManagedUserPlaylist to return an object for
   */
  public static function getInstance($resid = -1) {
    self::__wakeup();
    if (!is_string($resid)) {
      throw new MyURYException('Invalid ManagedUserPlaylistID!');
    }

    if (!isset(self::$playlists[$resid])) {
      self::$playlists[$resid] = new self($resid);
    }

    return self::$playlists[$resid];
  }
  
  /**
   * Get the User Playlist Name from the Folder path. This is "My Beds" or "My Jingles"
   * @param string $id Folder
   * @return string "My Beds" or "My Jingles"
   */
  public static function getNameFromFolder($id) {
    $data = explode('/', $id);
    switch ($data[sizeof($data)-1]) {
      case 'jingles':
        return 'My Jingles';
        break;
      case 'beds':
        return 'My Beds';
        break;
      default:
        return 'ERR_USR_PRESET_NOT_FOUND: '.$id;
        break;
    }
  }
  
  /**
   * Get the unique folder of the ManagedUserPlaylist
   * @return String
   */
  public function getID() {
    return $this->getFolder();
  }
  
  /**
   * Returns the managed user playlists for the given user
   * @param User $user
   * @return array of My Beds and My Jingles playlists for the user
   */
  public static function getAllManagedUserPlaylistsFor($user) {
    return array(
        self::getInstance($user->getID().'/beds'),
        self::getInstance($user->getID().'/jingles')
    );
  }
}

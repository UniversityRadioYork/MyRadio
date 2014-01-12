<?php

/**
 * This file provides the NIPSWeb_ManagedUserPlaylist class for MyRadio - Contains My Jingles and My Beds
 * @package MyRadio_NIPSWeb
 */

/**
 * The NIPSWeb_ManagedUserPlaylist class provide My Jingles and My Beds for users.
 * 
 * @version 20130802
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_ManagedUserPlaylist extends NIPSWeb_ManagedPlaylist {

  /**
   * Initiates the UserPlaylist variables
   * @param int $playlistid The folder of the user playlist to initialise, e.g. 7449/beds
   * Note: Only links *non-expired* items
   */
  protected function __construct($playlistid) {
    $this->folder = $playlistid;

    $this->name = self::getNameFromFolder($this->folder);
  }

  /**
   * Get the User Playlist Name from the Folder path. This is "My Beds" or "My Jingles"
   * @param string $id Folder
   * @return string "My Beds" or "My Jingles"
   */
  public static function getNameFromFolder($id) {
    $data = explode('/', $id);
    switch ($data[sizeof($data) - 1]) {
      case 'jingles':
        return 'My Jingles';
        break;
      case 'beds':
        return 'My Beds';
        break;
      default:
        return 'ERR_USR_PRESET_NOT_FOUND: ' . $id;
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
   * Return the NIPSWeb_ManagedItems that belong to this playlist
   * @return Array[NIPSWeb_ManagedItem]
   */
  public function getItems() {
    if (empty($this->items)) {
      $items = self::$db->fetch_column('SELECT manageditemid FROM bapsplanner.managed_user_items
      WHERE managedplaylistid=$1 ORDER BY title', array('membersmusic/' . $this->folder));
      $this->items = array();
      foreach ($items as $id) {
        /**
         * Pass this to the ManagedItem - it's called Dependency Injection and prevents loops and looks pretty
         * http://stackoverflow.com/questions/4903387/can-2-singleton-classes-reference-each-other
         * http://www.phparch.com/2010/03/static-methods-vs-singletons-choose-neither/
         */
        $this->items[] = NIPSWeb_ManagedItem::getInstance((int) $id, $this);
      }
    }
    return $this->items;
  }

  /**
   * Returns the managed user playlists for the given user
   * @param MyRadio_User $user
   * @return array of My Beds and My Jingles playlists for the user
   */
  public static function getAllManagedUserPlaylistsFor($user) {
    return array(
        self::getInstance($user->getID() . '/beds'),
        self::getInstance($user->getID() . '/jingles')
    );
  }

}

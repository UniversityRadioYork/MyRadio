<?php
/**
 * This file provides the iTones_PlaylistRevision class for MyURY - Contains history of an iTones_Playlist
 * @package MyURY_iTones
 */

/**
 * The iTones_PlaylistRevision class helps to manage previous versions of an iTones_Playlist
 * 
 * @version 20130714
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_iTones
 * @uses \Database
 */
class iTones_PlaylistRevision extends iTones_Playlist {
  /**
   * The Singleton store for PlaylistRevision objects
   * @var iTones_PlaylistRevision
   */
  private static $playlists = array();
  
  /**
   * When this revision was created
   * @var int
   */
  private $timestamp;
  
  /**
   * Who created this revision
   * @var User
   */
  private $author;
  
  /**
   * A commit message about the change
   * @var String
   */
  private $notes;
  
  /**
   * Initiates the PlaylistRevision variables
   * @param int $playlistid The ID of the managed playlist to initialise
   * @param int $revisionid The Revision of the managed playlist to initialise
   */
  protected function __construct($playlistid, $revisionid) {
    parent::__construct($playlistid);
    
    $result = self::$db->fetch_one('SELECT * FROM jukebox.playlist_revisions
      WHERE playlistid=$1 AND revisionid=$2 LIMIT 1',
            array($playlistid, $revisionid));
    if (empty($result)) {
      throw new MyURYException('The specified iTones Playlist Revision does not seem to exist');
      return;
    }
    
    $this->revisionid = $revisionid;
    $this->author = User::getInstance($result['author']);
    $this->notes = $result['notes'];
    $this->timestamp = strtotime($result['timestamp']);
    
    $items = self::$db->fetch_column('SELECT trackid FROM jukebox.playlist_entries WHERE playlistid=$1
      AND revision_added <= $2 AND (revision_removed >= $2 OR revision_removed IS NULL)
      ORDER BY entryid', array($this->getID(), $this->getRevisionID()));
    
    foreach ($items as $id) {
      $this->tracks[] = MyURY_Track::getInstance($id);
    }

  }
  
  /**
   * Returns the current instance of that Playlist object if there is one, or runs the constructor if there isn't
   * @param String $resid The ID of the Playlist to return an object for
   * @param int $revisionid The revision to load
   * @return iTones_Playlist
   */
  public static function getInstance($resid = -1, $revisionid = -1) {
    self::wakeup();
    if (!is_string($resid) or empty($resid)) {
      throw new MyURYException('Invalid iTonesPlaylistID!');
    }

    if (!isset(self::$playlists[$resid])) {
      self::$playlists[$resid] = new self($resid, $revisionid);
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
  
  public function getAuthor() {
    return $this->author;
  }
  
  public function getNotes() {
    return $this->notes;
  }
  
  public function getTimestamp() {
    return $this->timestamp;
  }
  
  /**
   * Prevents idiots attempting to edit this revision.
   */
  public function acquireOrRenewLock($lockstr = null, User $user = null) {
    throw new MyURYException('You can\'t lock an archived playlist revision, poopyhead!');
  }
  
  /**
   * Prevents idiots attempting to edit this revision.
   */
  public function setTracks($tracks, $lockstr = null) {
    throw new MyURYException('You can\'t lock an archived playlist revision, poopyhead!');
  }
  
  public static function getAllRevisions($playlistid) {
    $data = array();
    foreach (self::$db->fetch_column('SELECT revisionid FROM jukebox.playlist_revisions WHERE playlistid=$1',
            array($playlistid)) as $revisionid) {
      $data[] = self::getInstance($playlistid, $revisionid);
    }
    return $data;
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'revisionid' => $this->getRevisionID(),
        'timestamp' => $this->getTimestamp(),
        'notes' => $this->getNotes(),
        'author' => $this->getAuthor()->getName(),
        'viewtrackslink' => array('display' => 'icon',
            'value' => 'folder-open',
            'title' => 'View Tracks in this playlist revision',
            'url' => CoreUtils::makeURL('iTones', 'viewPlaylistRevision',
                    array('playlistid'=>$this->getID(), 'revisionid' => $this->getRevisionID()))),
        'restorelink' => array('display' => 'icon',
            'value' => 'refresh',
            'title' => 'Restore this revision',
            'url' => CoreUtils::makeURL('iTones', 'restorePlaylistRevision',
                    array('playlistid'=>$this->getID(), 'revisionid' => $this->getRevisionID()))),
        
    );
  }
}

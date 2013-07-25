<?php

/**
 * This file provides the MyURY_TrackCorrection class for MyURY
 * @package MyURY_Core
 */

/**
 * The MyURY_TrackCorrection class provides information and utilities for dealing with detecting a major issue
 * with the track metadata by the FingerprinterDaemon.
 * 
 * @version 20130720
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @todo Cache this
 */
class MyURY_TrackCorrection extends MyURY_Track {
  /**
   * A "recommended" correction proposal is one that is almost certainly correct, e.g. typo
   */
  const LEVEL_RECOMMEND = 1;
  /**
   * A "suggested" correction proposal is one that is possibly correct, e.g. incorrect information
   */
  const LEVEL_SUGGEST = 0;

  /**
   * The Singleton store for TrackCorrection objects
   * @var MyURY_TrackCorrection
   */
  private static $corrections = array();

  /**
   * The proposed title for the track
   * @var String
   */
  private $proposed_title;
  /**
   * The proposed artist for the track
   * @var String
   */
  private $proposed_artist;
  /**
   * The proposed album name for the track. This is *now* a MyURY_Album - The album may not exist yet.
   * @var String
   */
  private $proposed_album_name;
  
  /**
   * The ID of the Track Correction Proposal.
   * @var int
   */
  private $correctionid;
  
  /**
   * The User that has reviewed this Correction, if any.
   * @var null:User
   */
  private $reviewedby;
  
  /**
   * The recommendation level - one of the LEVEL_ constants
   * @var int 
   */
  private $level;

  /**
   * Initiates the Track variables
   * @param int $correctionid The ID of the track correction proposal to initialise
   * @todo Genre class
   * @todo Artist normalisation
   */
  protected function __construct($correctionid) {
    $this->correctionid = (int) $correctionid;
    $result = self::$db->fetch_one('SELECT * FROM public.rec_trackcorrection WHERE correctionid=$1 LIMIT 1',
            array($this->correctionid));
    if (empty($result)) {
      throw new MyURYException('The specified TrackCorrection does not seem to exist');
      return;
    }

    parent::__construct($result['trackid']);

    $this->proposed_title = $result['proposed_title'];
    $this->proposed_artist = $result['proposed_artist'];
    $this->proposed_album_name = $result['proposed_album_name'];
    $this->reviewedby = empty($result['reviewedby']) ? null : User::getInstance($result['reviewedby']);
    $this->level = (int)$result['level'];
  }

  /**
   * Returns the current instance of that TrackCorrection object if there is one, or runs the constructor if there isn't
   * @param int $correctionid The ID of the TrackCorrection to return an object for
   * 
   * @return MyURY_Track
   */
  public static function getInstance($correctionid = -1) {
    self::wakeup();
    if (!is_numeric($correctionid)) {
      throw new MyURYException('Invalid TrackCorrection ID!', 400);
    }

    if (!isset(self::$corrections[$correctionid])) {
      //See if there's one in the cache
      self::$corrections[$correctionid] = new self($correctionid);
    }

    return self::$corrections[$correctionid];
  }
  
  /**
   * Creates a new MyURY_TrackCorrection Proposal
   * @param MyURY_Track $track The Track to correct
   * @param String $title The proposed Title
   * @param String $artist The proposed Artist
   * @param String $album_name The proposed Album
   * @return MyURY_TrackCorrection The New Correction object
   */
  public static function create($track, $title = 'No Suggestion.',
          $artist = 'No Suggestion.', $album_name = 'No Suggestion.', $level = self::LEVEL_SUGGEST) {
    $r = self::$db->fetch_column('INSERT INTO public.rec_trackcorrection
      (trackid, proposed_title, proposed_artist, proposed_album_name, level)
      VALUES ($1, $2, $3, $4, $5) RETURNING correctionid',
            array($track->getID(), $title, $artist, $album_name, $level));
    
    if (empty($r)) return false;
    
    return self::getInstance((int)$r[0]);
  }
  
  /**
   * Get a random "Pending" track correction proposal, or null if there are no proposals
   * @return MyURY_TrackCorrection|null
   */
  public static function getRandom() {
    $result = self::$db->fetch_column('SELECT correctionid FROM public.rec_trackcorrection WHERE state=\'p\'
      ORDER BY RANDOM() LIMIT 1');
    
    if (empty($result)) return null;
    
    return self::getInstance($result[0]);
  }
  
  public function getProposedTitle() {
    return $this->proposed_title;
  }
  
  public function getProposedArtist() {
    return $this->proposed_artist;
  }
  
  public function getProposedAlbumTitle() {
    return $this->proposed_album_name;
  }
  
  public function getLevel() {
    return $this->level;
  }
  
  public function getCorrectionID() {
    return $this->correctionid;
  }
  
  public function apply() {
    //Don't apply a "URY Downloads" album - that's worse than whatever is already there.
    if (strstr($this->getProposedAlbumTitle(), 'URY Downloads') === false) {
      $this->setAlbum(MyURY_Album::findOrCreate($this->getProposedAlbumTitle(), $this->getProposedArtist()));
    }
    $this->setArtist($this->getProposedArtist());
    $this->setTitle($this->getProposedTitle());
    
    self::$db->query('UPDATE public.rec_trackcorrection SET state=\'a\', reviewedby=$2 WHERE correctionid=$1',
            array($this->getCorrectionID(), User::getInstance()->getID()));
    $this->state = 'a';
    return true;
  }
  
  public function reject($permanent = false) {
    self::$db->query('UPDATE public.rec_trackcorrection SET state=\'r\', reviewedby=$2 WHERE correctionid=$1',
            array($this->getCorrectionID(), User::getInstance()->getID()));
    
    if ($permanent) {
      $this->setLastfmVerified();
    }
  }

  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'title' => $this->getTitle(),
        'artist' => $this->getArtist(),
        'album' => $this->getAlbum()->toDataSource(),
        'trackid' => $this->getID(),
        'proposed_title' => $this->getProposedTitle(),
        'proposed_artist' => $this->getProposedArtist(),
        'proposed_album' => $this->getProposedAlbumTitle(),
        'level' => $this->getLevel(),
        'correctionid' => $this->getCorrectionID(),
        'editlink' => array(
            'display' => 'icon',
            'value' => 'script',
            'title' => 'Edit Track Manually',
            'url' => CoreUtils::makeURL('Library', 'editTrack', array('trackid' => $this->getID()))
        ),
        'confirmlink' => array(
            'display' => 'icon',
            'value' => 'circle-check',
            'title' => 'Approve Track Correction',
            'url' => CoreUtils::makeURL('Library', 'acceptTrackCorrection', array('correctionid' => $this->getCorrectionID()))
        )
        ,
        'rejectlink' => array(
            'display' => 'icon',
            'value' => 'trash',
            'title' => 'Reject Track Correction',
            'url' => CoreUtils::makeURL('Library', 'rejectTrackCorrection', array('correctionid' => $this->getCorrectionID()))
        )
    );
  }

}

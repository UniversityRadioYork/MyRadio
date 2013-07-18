<?php

/**
 * This file provides the MyURY_TrackCorrection class for MyURY
 * @package MyURY_Core
 */

/**
 * The MyURY_TrackCorrection class provides information and utilities for dealing with detecting a major issue
 * with the track metadata by the FingerprinterDaemon.
 * 
 * @version 20130609
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @todo Cache this
 */
class MyURY_TrackCorrection extends MyURY_Track {

  /**
   * The Singleton store for TrackCorrection objects
   * @var MyURY_TrackCorrection
   */
  private static $corrections = array();

  /**
   * The proposed title for the track
   * @var String
   */
  private static $proposed_title;
  /**
   * The proposed artist for the track
   * @var String
   */
  private static $proposed_artist;
  /**
   * The proposed album name for the track. This is *now* a MyURY_Album - The album may not exist yet.
   * @var String
   */
  private static $proposed_album_name;
  
  /**
   * The ID of the Track Correction Proposal.
   * @var int
   */
  private static $correctionid;
  
  /**
   * The User that has reviewed this Correction, if any.
   * @var null:User
   */
  private static $reviewedby;

  /**
   * Initiates the Track variables
   * @param int $correctionid The ID of the track correction proposal to initialise
   * @todo Genre class
   * @todo Artist normalisation
   */
  private function __construct($correctionid) {
    $this->correctionid = (int) $correctionid;
    $result = self::$db->fetch_one('SELECT * FROM public.rec_trackcorrection WHERE correctionid=$1 LIMIT 1', array($this->trackid));
    if (empty($result)) {
      throw new MyURYException('The specified TrackCorrection does not seem to exist');
      return;
    }

    parent::__construct($result['trackid']);

    $this->proposed_title = $result['proposed_title'];
    $this->proposed_artist = $result['proposed_artist'];
    $this->proposed_album_name = $result['proposed_album_name'];
    $this->reviewedby = empty($result['reviewedby']) ? null : User::getInstance($result['reviewedby']);
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
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'title' => $this->getTitle(),
        'artist' => $this->getArtist(),
        'album' => $this->getAlbum()->getID(),
        'trackid' => $this->getID(),
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

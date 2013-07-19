<?php
/**
 * Provides the MyURY_PlaylistsDaemon class for MyURY
 * @package MyURY_Daemon
 */

/**
 * This Daemon updates the auto-generated iTones Playlists once an hour.
 * 
 * @version 20130710
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Tracklist
 * @uses \Database
 * 
 */
class MyURY_PlaylistsDaemon extends MyURY_Daemon {
  public static function isEnabled() { return true; }
  
  public static function run() {
    $hourkey = __CLASS__.'_last_run_hourly';
    if (self::getVal($hourkey) > time() - 3500) return;
    
    self::updateMostPlayedPlaylist();
    self::updateNewestUploadsPlaylist();
    
    //Done
    self::setVal($hourkey, time());
  }
  
  private static function updateMostPlayedPlaylist() {
    $pobj = iTones_Playlist::getInstance('semantic-auto');
    $lockstr = $pobj->acquireOrRenewLock(null, User::getInstance(Config::$system_user));
    
    /**
     * @todo This is 70 days for testing (It was Summer when I wrote this...)
     */
    $most_played = MyURY_TracklistItem::getTracklistStatsForBAPS(time() - (86400 * 70)); //Track play stats for last week
    
    $playlist = array();
    for ($i = 0; $i < 100; $i++) {
      if (!isset($most_played[$i])) break; //If there aren't that many, oh well.
      $playlist[] = MyURY_Track::getInstance($most_played[$i]['trackid']);
    }
    
    $pobj->setTracks($playlist, $lockstr);
    $pobj->releaseLock($lockstr);
  }
  
  private static function updateNewestUploadsPlaylist() {
    $pobj = iTones_Playlist::getInstance('newest-auto');
    $lockstr = $pobj->acquireOrRenewLock(null, User::getInstance(Config::$system_user));
    
    $newest_tracks = NIPSWeb_AutoPlaylist::findByName('Newest Tracks')->getTracks();
    
    $pobj->setTracks($newest_tracks, $lockstr);
    $pobj->releaseLock($lockstr);
  }
}
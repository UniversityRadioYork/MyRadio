<?php
/**
 * This file provides the MyURY_PodcastDeamon class for MyURY
 * @package MyURY_Daemon
 */

/**
 * The Podcast Daemon converts uploaded audio files into web-ready uryplayer files.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130817
 * @package MyURY_Daemon
 */
class MyURY_PodcastDaemon extends MyURY_Daemon {
  /**
   * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
   * @return boolean
   */
  public static function isEnabled() { return Config::$d_Podcast_enabled; }
  
  public static function run() {
    dlog('Checking for pending Podcasts...', 4);
    $podcasts = MyURY_Podcast::getPending();
    
    if (!empty($podcasts)) {
      //Encode the first podcast.
      dlog('Converting Podcast '.$podcasts[0]->getID().'...', 3);
      $podcasts[0]->convert();
      dlog('Converstion complete.', 3);
    }
  }
}
<?php
/**
 * This file provides the MyURY_LabelFinderDaemon class for MyURY
 * @package MyURY_Daemon
 */

/**
 * The LabelFinder Daemon processes rec_record in batches, and tries to fill in the "label" field.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyURY_Daemon
 */
class MyURY_LabelFinderDaemon extends MyURY_Daemon {
  /**
   * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
   * It is currently enabled because we have a lot of labels that needed filling in for Tracklisting.
   * @return boolean
   */
  public static function isEnabled() { return true; }
  
  /**
   * THE DISCOGS API IS RATE LIMITED TO ONE REQUEST PER SECOND.
   */
  public static function run() {
    //Get 5 albums without labels
    $albums = Database::getInstance()->fetch_all('SELECT recordid, title, artist FROM public.rec_record
      WHERE recordlabel=\'\' ORDER BY RANDOM() LIMIT 5');
    
    foreach ($albums as $album) {
      $data = json_decode(file_get_contents('http://api.discogs.com/database/search?artist='.urlencode($album['artist'])
              .'&release_title='.urlencode($album['title']).'&type=release'), true);
      
      if (!empty($data['results'])) {
        $label = $data['results'][0]['label'][0];
        
        dlog("Setting {$album['recordid']} label to {$label}", 2);
        Database::getInstance()->query('UPDATE public.rec_record SET recordlabel=$1 WHERE recordid=$2',
                array($label, $album['recordid']));
      }
      sleep(1);
    }
  }
}
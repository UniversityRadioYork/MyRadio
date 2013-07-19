<?php

class MyURY_LabelFinderDaemon {
  public static function isEnabled() { return true; }
  
  /**
   * THIS API IS RATE LIMITED TO ONE REQUEST PER SECOND.
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
        
        echo "Setting {$album['recordid']} label to {$label}.\n";
        Database::getInstance()->query('UPDATE public.rec_record SET recordlabel=$1 WHERE recordid=$2',
                array($label, $album['recordid']));
      }
      sleep(1);
    }
  }
}
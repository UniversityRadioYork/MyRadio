<?php

class MyURY_FingerprinterDaemon {
  public static function isEnabled() { return true; }
  
  public static function run() {
    //Get 5 unverified tracks
    $tracks = MyURY_Track::findByOptions(array('lastfmverified' => false, 'random' => true, 'digitised' => true), 5);
    
    foreach ($tracks as $track) {
      $info = MyURY_Track::identifyUploadedTrack($track->getPath());
      
      /**
       * We use two metrics to identify if the information is reliable
       * 1. Is the rank high (> 0.8)?
       * 2. Does is have a short levenshtein difference from the current value (in this case just log the suggestion)
       */
      if ($info[0]['rank'] < 0.8) {
        echo 'Fingerprint data for '.$track->getID()." unreliable. Skipping.\n";
        continue;
      }
      
      if ($track->getTitle() == $info[0]['title']
              && $track->getArtist() == $info[0]['artist']) {
        echo "Track {$track->getID()} verified as correct.\n";
        
        $track->setLastfmVerified();
        continue;
      }
      
      if (levenshtein($info[0]['title'], $track->getTitle()) > 8) {
        echo "Fingerprint data for {$track->getID()} suggests that current title {$track->getTitle()} should be {$info[0]['title']}. Significantly different, skipping.\n";
        continue;
      }
      
      if (levenshtein($info[0]['artist'], $track->getArtist()) > 5) {
        echo "Fingerprint data for {$track->getID()} suggests that current artist {$track->getArtist()} should be {$info[0]['artist']}. Significantly different, skipping.\n";
        continue;
      }
      
      echo "Recommend changing {$track->getID()} from {$track->getTitle()} by {$track->getArtist()} to {$info[0]['title']} by {$info[0]['artist']}.\n";
    }
  }
}
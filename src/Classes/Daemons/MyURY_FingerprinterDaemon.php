<?php

class MyURY_FingerprinterDaemon extends MyURY_Daemon {
  public static function isEnabled() { return true; }
  
  public static function run() {
    //Get 5 unverified tracks
    $tracks = MyURY_Track::findByOptions(array('lastfmverified' => false, 'random' => true, 'digitised' => true,
        'nocorrectionproposed' => true, 'limit' => 50));
    
    foreach ($tracks as $track) {
      $info = MyURY_Track::identifyUploadedTrack($track->getPath());
      
      /**
       * We use two metrics to identify if the information is reliable
       * 1. Is the rank high (> 0.8)?
       * 2. Does is have a short levenshtein difference from the current value?
       */
      if (empty($info[0]) or $info[0]['rank'] < 0.8) {
        echo 'Fingerprint data for '.$track->getID()." unreliable (p={$info[0]['rank']}). Skipping.\n";
        continue;
      }
      
      if ($info[0]['title'] !== $track->getTitle() && levenshtein($info[0]['title'], $track->getTitle()) <= 2) {
        echo "Minor title correction made - {$track->getTitle()} to {$info[0]['title']}\n";
        $track->setTitle($info[0]['title']);
      }
      
      if ($info[0]['artist'] !== $track->getArtist() && levenshtein($info[0]['artist'], $track->getArtist()) <= 2) {
        echo "Minor artist correction made - {$track->getArtist()} to {$info[0]['artist']}\n";
        $track->setArtist($info[0]['artist']);
      }
      
      if ($track->getTitle() == $info[0]['title']
              && $track->getArtist() == $info[0]['artist']) {
        echo "Track {$track->getID()} verified as correct.\n";
        
        $track->setLastfmVerified();
        continue;
      }
      
      $album = MyURY_Track::getAlbumDurationAndPositionFromLastfm($info[0]['title'], $info[0]['artist'])['album']->getTitle();
      
      if (levenshtein($info[0]['title'], $track->getTitle()) < 8
              or levenshtein($info[0]['artist'], $track->getArtist()) < 5
              or levenshtein($album, $track->getAlbum()->getTitle()) < 5) {
        MyURY_TrackCorrection::create($track, $info[0]['title'], $info[0]['artist'], $album, MyURY_TrackCorrection::LEVEL_RECOMMEND);
        echo "Correction recommended for {$track->getID()}.\n";
      } else {
        MyURY_TrackCorrection::create($track, $info[0]['title'], $info[0]['artist'], $album, MyURY_TrackCorrection::LEVEL_SUGGEST);
        echo "Correction suggested {$track->getID()}.\n";
      }
    }
  }
}
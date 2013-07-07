<?php

class MyURY_StatsGenDaemon {
  private static $lastrunhourly = 0;
  private static $lastrundaily = 0;
  
  public static function isEnabled() { return true; }
  
  public static function run() {
    if (self::$lastrunhourly > time() - 3600) return;
    
    //Generate Training Graph
    self::generateTrainingGraph();
    
    //Do dailies?
    if (self::$lastrundaily <= time() - 86400) {
      
      self::generateJukeboxReport();
      
      self::$lastrundaily = time();
    }
    
    //Done
    self::$lastrunhourly = time();
  }
  
  private static function generateTrainingGraph() {
    $trained = User::findAllTrained();
    $demoed = User::findAllDemoed();
    $trainers = User::findAllTrainers();
    
    $dotstr = 'digraph { overlap=false; splines=false; ';
    
    foreach ($trained as $user) {
      $dotstr .= '"'.$user->getEmail().'" -> "'.$user->getStudioTrainedBy()->getEmail().'"; ';
    }
    
    //Red for demos
    $dotstr .= 'edge [color=red]; ';
    
    foreach ($demoed as $user) {
      $dotstr .= '"'.$user->getEmail().'" -> "'.$user->getStudioDemoedBy()->getEmail().'"; ';
    }
    
    //Green for trainers
    $dotstr .= 'edge [color=green]; ';
    
    foreach ($trainers as $user) {
      $dotstr .= '"'.$user->getEmail().'" -> "'.$user->getTrainerTrainedBy()->getEmail().'"; ';
    }
    
    $dotstr .= '}';
    
    passthru("echo '$dotstr' | /usr/local/bin/sfdp -Tsvg > ".__DIR__.'/../../Public/img/stats_training.svg');
  }
  
  /**
   * Once a day, this emails the Reporting List with a table of all tracks iTones has played in the last 24 hours
   * It's useful to see if it's got into bad habits such as playing the same song 3 million times.
   */
  private static function generateJukeboxReport() {
    $info = MyURY_TracklistItem::getTracklistStatsForJukebox(time()-86400);
    
    $totalplays = 0;
    $totaltracks = 0;
    $totaltime = 0;
    $table = '<table><tr><th>Number of Plays</th><th>Title</th><th>Total Playtime</th><th>Playlist Membership</th></tr>';
    
    foreach ($info as $row) {
      $table .= '<tr><td>'.$row['num_plays'].'</td><td>'.$row['title'].'</td><td>'.$row['total_playtime'].'</td><td>'
              . $row['in_playlists'] .'</td></tr>'."\r\n";
      $totalplays += $row['num_plays'];
      $totaltracks++;
      $totaltime += $row['total_playtime'];
    }
    
    $table .= '<tr><th>'.$totalplays.'</th><th>'.$totaltracks.'</th><th>'.CoreUtils::intToTime($totaltime).'</th></tr>';
    $table .= '</table>';
    
    MyURYEmail::sendEmailToList(MyURY_List::getByName(Config::$reporting_list), 'Jukebox Playout Report', $table);
  }
}
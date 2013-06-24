<?php

class MyURY_StatsGenDaemon {
  private static $lastrun = 0;
  
  public static function isEnabled() { return true; }
  
  public static function run() {
    if (self::$lastrun > time() - 3600) return;
    
    //Generate Training Graph
    self::generateTrainingGraph();
    
    
    //Done
    self::$lastrun = time();
  }
  
  public static function generateTrainingGraph() {
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
}
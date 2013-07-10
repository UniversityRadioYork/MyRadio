<?php

/**
 * This file provides the iTones_Utils class
 * @package MyURY_iTones
 */

/**
 * The iTones_Utils class provides generic utilities for controlling iTones - URY's Campus Jukebox
 * 
 * @version 20130710
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_iTones
 * @uses \Database
 */
class iTones_Utils extends ServiceAPI {
  
  private static $telnet_handle;
  private static $queues = array('requests', 'main');

  /**
   * Push a track into the iTones request queue.
   * @param MyURY_Track $track
   * @param $queue The jukebox_[x] queue to push to. Default requests. "main" is the queue used for the main track
   * scheduler, i.e. non-user entries.
   * @return bool Whether the operation was successful
   */
  public static function requestTrack(MyURY_Track $track, $queue = 'requests') {
    self::verifyQueue($queue);
    $r = self::telnetOp('jukebox_'.$queue.'.push '.$track->getPath());
    return is_numeric($r);
  }
  
  /**
   * Returns Request IDs and Track IDs currently in the queue
   * @param String $queue Optional, as per definition in requestTrack()
   * @return Array 2D, such as [['requestid' => 1, 'trackid' => 72830], ...]
   */
  public static function getTracksInQueue($queue = 'requests') {
    self::verifyQueue($queue);
    $info = explode(' ', self::telnetOp('jukebox_'.$queue.'.queue'));
    
    $items = array();
    foreach ($info as $item) {
      if (is_numeric($item)) {
        $tid = preg_replace('/^.*\"'.str_replace('/','\\/', Config::$music_central_db_path)
                .'\/records\/[0-9]+\/([0-9]+)\.mp3.*$/is', '$1', self::telnetOp('request.trace '.$item));
        $items[] = array('requestid' => (int)$item, 'trackid' => (int)$tid);
      }
    }
    return $items;
  }
  
  /**
   * Check if a track is currently queued to be played in any queue.
   * @return boolean
   */
  public static function getIfQueued(MyURY_Track $track) {
    foreach (self::$queues as $queue) {
      $r = self::getTracksInQueue($queue);
      foreach ($r as $req) {
        if ($req['trackid'] === $track->getID()) return true;
      }
    }
    return false;
  }
  
  private static function verifyQueue($queue) {
    if (!in_array($queue, self::$queues)) throw new MyURYException('Invalid Queue!');
  }
  
  /**
   * Runs a telnet command
   * @param String $command
   * @return String
   */
  private static function telnetOp($command) {
    if (empty(self::$telnet_handle)) {
      self::telnetStart();
    }

    fwrite(self::$telnet_handle, $command . "\n");

    $response = fread(self::$telnet_handle, 1048576); //Read a max of 1MB of data
    
    
    
    //Remove the END
    return trim(str_replace('END', "\n", $response));
  }
  
  private static function telnetStart() {
    self::$telnet_handle = fsockopen('tcp://'.Config::$itones_telnet_host, Config::$itones_telnet_port, $errno,
            $errstr, 10);
    register_shutdown_function(array(__CLASS__, 'telnetEnd'));
  }
  
  public static function telnetEnd() {
    fwrite(self::$telnet_handle, "quit\n");
    fclose(self::$telnet_handle);
  }

}

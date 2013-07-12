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
   * @return Array 2D, such as [['requestid' => 1, 'trackid' => 72830, 'queue' => 'requests'], ...]
   */
  public static function getTracksInQueue($queue = 'requests') {
    self::verifyQueue($queue);
    $info = explode(' ', self::telnetOp('jukebox_'.$queue.'.queue'));
    
    $items = array();
    foreach ($info as $item) {
      if (is_numeric($item)) {
        $tid = preg_replace('/^.*\"'.str_replace('/','\\/', Config::$music_central_db_path)
                .'\/records\/[0-9]+\/([0-9]+)\.mp3.*$/is', '$1', self::telnetOp('request.trace '.$item));
        $items[] = array('requestid' => (int)$item, 'trackid' => (int)$tid, 'queue' => $queue);
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
  
  /**
   * Goes through the Queues, removing duplicate items
   * 
   * @return int The number of tracks that were removed from queues
   */
  public static function removeDuplicateItemsInQueues() {
    //Get the tracks in all the queues
    $tracks = array();
    foreach (self::$queues as $queue) {
      $tracks = array_merge($tracks, self::getTracksInQueue($queue));
    }
    
    //Go over each track, marking it as identified. If it's encountered a second time, kill it.
    $found = array();
    $removed = 0;
    foreach ($tracks as $track) {
      if (in_array($track['trackid'], $found)) {
        self::removeRequestFromQueue($track['queue'], $track['requestid']);
        $removed++;
      } else {
        $found[] = $track['trackid'];
      }
    }
    
    return $removed;
  }
  
  private static function removeRequestFromQueue($queue, $requestid) {
    self::verifyQueue($queue);
    
    self::telnetOp('jukebox_'.$queue.'.ignore '.$requestid);
  }
  
  private static function verifyQueue($queue) {
    if (in_array($queue, self::$queues) === false) throw new MyURYException('Invalid Queue!');
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
    $response = '';
    $line = '';
    do {
      $response .= $line;
      $line = fgets(self::$telnet_handle, 1048576); //Read a max of 1MB of data
    } while (trim($line) !== 'END');
    
    
    
    //Remove the END
    return trim($response);
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

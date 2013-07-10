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

  /**
   * Push a track into the iTones request queue.
   * @param MyURY_Track $track
   * @param $queue The jukebox_[x] queue to push to. Default requests. "main" is the queue used for the main track
   * scheduler, i.e. non-user entries.
   * @return bool Whether the operation was successful
   */
  public static function requestTrack(MyURY_Track $track, $queue = 'requests') {
    if ($queue !== 'requests' && $queue !== 'main') throw new MyURYException('Invalid Queue!');
    $r = self::telnetOp('jukebox_'.$queue.'.push '.$track->getPath());
    return is_numeric($r);
  }
  
  private static function telnetOp($command) {
    $h = fsockopen('tcp://'.Config::$itones_telnet_host, Config::$itones_telnet_port, $errno, $errstr, 10);

    fwrite($h, $command . "\n");

    $response = fread($h, 1048576); //Read a max of 1MB of data
    
    fwrite($h, "quit\n");
    
    //Remove the END
    return trim(str_replace('END', "\n", $response));
  }

}

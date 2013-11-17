<?php

/**
 * Provides the MyRadio_Selector class for MyRadio
 * @package MyRadio_Selector
 */

/**
 * The Selector class provies an abstractor to the `sel` service
 * and the Selector logs.
 * 
 * BE CAREFUL USING SET METHOD IN THIS CLASS.
 * THEY *WILL* CHANGE THE STATION OUTPUT.
 * 
 * @version 20130813
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 */
class MyRadio_Selector {

  /**
   * The current studio is Studio 1
   */
  const SEL_STUDIO1 = 1;

  /**
   * The current studio is Studio 2
   */
  const SEL_STUDIO2 = 2;

  /**
   * The current studio is Jukebox
   */
  const SEL_JUKEBOX = 3;

  /**
   * The current studio is Outside Broadcast
   */
  const SEL_OB = 4;

  /**
   * The studio selection was made by the Selector Telnet interface
   */
  const FROM_AUX = 0;

  /**
   * The studio selection was made by Studio 1
   */
  const FROM_S1 = 1;

  /**
   * The studio selection was made by Studio 2
   */
  const FROM_S2 = 2;

  /**
   * The studio selection was made on the main selector panel in the hub
   */
  const FROM_HUB = 3;

  /**
   * The selector is unlocked
   */
  const LOCK_NONE = 0;

  /**
   * The selector has been locked remotely. The physical selector buttons
   * have no effect. This API still responds.
   */
  const LOCK_AUX = 1;

  /**
   * The selector has been locked physically. The physical selector buttons
   * and this API do not respond.
   */
  const LOCK_KEY = 2;

  /**
   * No studios are on.
   */
  const ON_NONE = 0;

  /**
   * Studio 1 is switched on.
   */
  const ON_S1 = 1;

  /**
   * Studio 2 is switched on.
   */
  const ON_S2 = 2;

  /**
   * Both studios are switched on.
   */
  const ON_BOTH = 3;

  /**
   * Caches the status of the selector (the query command)
   * @var Array
   */
  private $sel_status;

  /**
   * Construct the Selector Object
   */
  public function __construct() {
    
  }
  
  /**
   * Returns the state of the remote OB feeds in an associative array.
   * @return Array
   */
  public function remoteStreams() {
    $data = file(Config::$ob_remote_status_file,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $response = [];
    foreach ($data as $feed) {
      $state = explode('=',$feed);
      $response[trim($state[0])] = (bool)trim($state[1]);
    }
    
    return $response;
  }
  
  /**
   * Returns the length of the current silence, if any.
   * @return int
   */
  public function isSilence() {
    $result = Database::getInstance()->fetch_one('SELECT starttime, stoptime
      FROM jukebox.silence_log
      ORDER BY silenceid DESC LIMIT 1');
    
    if (empty($result['stoptime'])) {
      return time()-strtotime($result['starttime']);
    } else {
      return 0;
    }
  }

  /**
   * Returns the current selector status
   * 
   * The command 'Q' returns a 4-digit number. The first digit is the currently
   * selected studio. The second is where it was selected from, the third
   * provides information about whether the selector is locked, and the fourth
   * about which studios are switched on.
   * 
   * @return Array {'studio' => [1-8], 'selectedfrom' => [0-3], 'lock' => [0-2],
   * 'power' => [0-3]}
   */
  public function query() {
    if (empty($this->sel_status)) {
      $data = $this->cmd('Q');
      
      $state = str_split($data);

      $this->sel_status = [
          'studio' => (int)$state[0],
          'lock' => (int)$state[1],
          'selectedfrom' => (int)$state[2],
          'power' => (int)$state[3]
      ];
    }

    return $this->sel_status;
  }

  /**
   * Runs a command against URY's Physical Studio Selector. Be careful.
   * @param String $cmd (Q)uery, (L)ock, (U)nlock, S[1-8]
   * @return String Status for Query, or ACK/FLK for other commands.
   */
  private function cmd($cmd) {
    $h = fsockopen('tcp://' . Config::$selector_telnet_host,
            Config::$selector_telnet_port, $errno, $errstr, 10);

    //Read through the welcome "studio selector:" message (16x2bytes)
    fgets($h, 32);
    
    //Run command
    fwrite($h, $cmd . "\n");
    
    //Read response (4x2bytes)
    $response = fgets($h, 16);
    
    fclose($h);

    //Remove the END
    return trim($response);
  }
  
  /**
   * Returns what studio was on air at the time given
   * @param int $time
   * @return int
   */
  public static function getStudioAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT action FROM public.selector WHERE time <= $1
             AND action >= 4 AND action <= 11 
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return $result[0]-3;
  }

  /**
   * Returns where the selector was set from at the time given
   * @param int $time
   * @return int
   */
  public static function getSetbyAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT setby FROM public.selector WHERE time <= $1
             AND action >= 4 AND action <= 11 
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return $result[0];
  }

  /**
   * Returns the power state of studio1 at the time given
   * @param int $time
   * @return bool
   */
  public static function getStudio1PowerAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT action FROM public.selector WHERE time <= $1
             AND action >= 13 AND action <= 14 
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return ($result[0] == 13) ? true : false;
  }

  /**
   * Returns the power state of studio2 at the time given
   * @param int $time
   * @return bool
   */
  public static function getStudio2PowerAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT action FROM public.selector WHERE time <= $1
             AND action >= 15 AND action <= 16 
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return ($result[0] == 15) ? true : false;
  }

  /**
   * Returns the lock state at the time given
   * @param int $time
   * @return int
   */
  public static function getLockAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT action FROM public.selector WHERE time <= $1
             AND action >= 1 AND action <= 3 
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return $result[0]-1;
  }

  /**
   * Returns the time last modified before the time given
   * @param int $time
   * @return int
   */
  public static function getLastModAtTime($time) {
    $result = Database::getInstance()->fetch_column(
            'SELECT time FROM public.selector WHERE time <= $1
             ORDER BY time DESC
             LIMIT 1',
            [CoreUtils::getTimestamp($time)]);
    return $result[0]-1;
  }

  /**
   * Returns the selector status at the time given
   * @param int $time
   * @return array
   */
  public static function getStatusAtTime($time) {
    return array(
      'studio' => self::getStudioAtTime($time),
      'lock' => self::getLockAtTime($time),
      'selectedfrom' => self::getSetbyAtTime($time),
      's1power' => self::getStudio1PowerAtTime($time),
      's2power' => self::getStudio2PowerAtTime($time),
      'lastmod' => self::getLastModAtTime($time)
      );
  }

}
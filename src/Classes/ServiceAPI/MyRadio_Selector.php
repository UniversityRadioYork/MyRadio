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
  public function getStudioAtTime($time) {
    $result = self::$db->fetch_column(
            'SELECT action FROM public.selector WHERE time <= $1'
            .' AND action >= 4 AND action <= 11 ORDER BY time DESC LIMIT 1',
            CoreUtils::getTimestamp($time));
    return $result[0]-3;
  }

}
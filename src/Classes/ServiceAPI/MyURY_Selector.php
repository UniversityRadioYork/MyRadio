<?php

/**
 * Provides the MyURY_Selector class for MyURY
 * @package MyURY_Selector
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
 * @package MyURY_Core
 * @uses \Database
 */
class MyURY_Selector extends ServiceAPI {

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
   * Bot studios are switched on.
   */
  const ON_BOTH = 3;

  /**
   * Caches the status of the selector (the query command)
   * @var Array
   */
  private $sel_status;

  /**
   * Get a new Selector object
   * @param null $key
   * @return MyURY_Selector
   */
  public static function getInstance($key = null) {
    return new self();
  }

  /**
   * Construct the Selector Object
   */
  public function __construct() {
    
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
          'studio' => $state[0],
          'selectedfrom' => $state[1],
          'lock' => $state[2],
          'power' => $state[3]
      ];
    }

    return $this->sel_status;
  }

  private function cmd($cmd) {
    $h = fsockopen('tcp://' . Config::$selector_telnet_host,
            Config::$selector_telnet_port, $errno, $errstr, 10);

    //Read through the welcome
    do {
      fgets($h, 32);
    } while (!feof($h));
    
    //Run command
    fwrite($h, $cmd . "\n");
    
    //Let it think for a moment
    usleep(10000);
    
    //Read response
    $response .= fgets($h, 16);
    
    fclose($h);

    echo 'DATA:' . $response;
    //Remove the END
    return trim($response);
  }

}
<?php
/**
 * Provides the MyURY_Webcam class
 * @package MyURY_Webcam
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */

/**
 * @todo Document
 */
class MyURY_Webcam extends ServiceAPI {
  
  public static function getStreams() {
    return self::$db->fetch_all('SELECT * FROM webcam.streams ORDER BY streamid ASC');
  }
  
}
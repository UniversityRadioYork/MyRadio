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
  
  public static function incrementViewCounter(User $user) {
    //Get the current view counter. We do this as a separate query in case the row doesn't exist yet
    $counter = self::$db->fetch_one('SELECT timer FROM webcam.memberviews WHERE memberid = $1', array($user->getID()));
    if (empty($counter)) {
      $counter = 0;
      $sql = 'INSERT INTO webcam.memberviews (memberid, timer) VALUES ($1, $2)';
    } else {
      $counter = $counter['timer'];
      $sql = 'UPDATE webcam.memberviews SET timer=$2 WHERE memberid=$1';
    }
    $counter += 15;
    
    self::$db->query($sql, array($user->getID(), $counter));
    return $counter;
  }
  
}
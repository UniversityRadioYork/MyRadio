<?php
/**
 * Provides the MyRadio_Webcam class
 * @package MyRadio_Webcam
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */

/**
 * @todo Document
 */
class MyRadio_Webcam extends ServiceAPI {
  
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
  
  /**
   * Returns the available range of times for the Webcam Archives
   */
  public static function getArchiveTimeRange() {
    $files = scandir(Config::$webcam_archive_path);
    $earliest = time();
    $latest = time();
    foreach ($files as $file) {
      //Files are stored in the format yyyymmddhh-cam.mpg, if it doesn't match that pattern, skip it
      if (!preg_match('/^[0-9]{10}\-[a-zA-Z0-9\-]+\.mpg$/', $file)) continue;
      //Get a nicer timestamp format PHP can work with
      $str = preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2}).*$/', '$1-$2-$3 $4:00:00', $file);
      $time = strtotime($str);
      if ($time < $earliest) $earliest = $time;
      if ($time > $latest) $latest = $time;
    }
    echo $earliest.'='.$latest;
  }
  
}
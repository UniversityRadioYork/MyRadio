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
  
  /**
   * Returns the id and location of the currentl selected webcam
   * @return array webcam id and location
   */
  public static function getCurrentWebcam() {
    $current = file_get_contents(Config::$webcam_current_url);
        
    switch ($current) {
      case '0': $location = 'Jukebox';
        break;
      case '1': $location = 'Studio 1';
        break;
      case '2': $location = 'Studio 2';
        break;
      case '4': $location = 'Office';
        break;
      case '5': $location = 'Studio 1 Secondary';
        break;
      default: $location = $current;
        $current = 6;
        break;
    }

    return [
      'current' => self::cam_to_stream($current),
      'webcam' => $location
      ];
  }

  /**
   * [setWebcam description]
   * @param [type] $id [description]
   */
  public static function setWebcam($id) {
    if (($id === 0) || ($id === 1) || ($id === 2) || ($id === 4) || ($id === 5)) {
      file_get_contents(Config::$webcam_set_url.stream_to_cam($id));
    }
  }

  // @todo: remove the need for this function after migration to new by makeing camserver use streamids
  private static function cam_to_stream($id) {
    switch ($id) {
      case '0': return 1;
      case '1': return 2;
      case '2': return 4;
      case '4': return 5;
      case '5': return 3;
    };
  }

  // @todo: remove the need for this function after migration to sis4 by makeig camserver use streamids
  private static function stream_to_cam($id) {
    switch ($id) {
      case '1': return 0;
      case '2': return 1;
      case '3': return 5;
      case '4': return 2;
      case '5': return 4;
    };
  }
}
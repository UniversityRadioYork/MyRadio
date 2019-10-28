<?php
/**
 * Provides the MyRadio_Webcam class.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;

/**
 * @todo Document
 */
class MyRadio_Webcam extends ServiceAPI
{
    public static function getStreams()
    {
        return self::$db->fetchAll('SELECT * FROM webcam.streams ORDER BY streamid ASC');
    }

    public static function incrementViewCounter(MyRadio_User $user)
    {
        //Get the current view counter. We do this as a separate query in case the row doesn't exist yet
        $counter = self::getViewCounter($user);
        if (empty($counter)) {
            $counter = 0;
            $sql = 'INSERT INTO webcam.memberviews (memberid, timer) VALUES ($1, $2)';
        } else {
            $counter = $counter['timer'];
            $sql = 'UPDATE webcam.memberviews SET timer=$2 WHERE memberid=$1';
        }
        $counter += 15;

        self::$db->query($sql, [$user->getID(), $counter]);

        return $counter;
    }

    public static function getViewCounter(MyRadio_User $user)
    {
        $counter = self::$db->fetchOne('SELECT timer FROM webcam.memberviews WHERE memberid = $1', [$user->getID()]);
        return $counter['timer'];
    }

    /**
     * Returns the available range of times for the Webcam Archives.
     */
    public static function getArchiveTimeRange()
    {
        $files = scandir(Config::$webcam_archive_path);
        $earliest = time();
        $latest = time();
        foreach ($files as $file) {
            //Files are stored in the format yyyymmddhh-cam.mpg, if it doesn't match that pattern, skip it
            if (!preg_match('/^[0-9]{10}\-[a-zA-Z0-9\-]+\.mpg$/', $file)) {
                continue;
            }
            //Get a nicer timestamp format PHP can work with
            $str = preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2}).*$/', '$1-$2-$3 $4:00:00', $file);
            $time = strtotime($str);
            if ($time < $earliest) {
                $earliest = $time;
            }
            if ($time > $latest) {
                $latest = $time;
            }
        }
        echo $earliest.'='.$latest;
    }

    /**
     * Returns the id and location of the currentl selected webcam.
     *
     * @return array webcam id and location
     */
    public static function getCurrentWebcam()
    {
        if (Config::$webcam_current_url) {
            $response = file_get_contents(Config::$webcam_current_url);
            $response = json_decode($response, true);
            $streams = self::getStreams();
            switch ($response['camera']) {
                case 'cam1':
                    $location = 'Jukebox';
                    break;
                case 'cam2':
                    $location = 'Outside Broadcast';
                    break;
                case 'offair':
                    $location = 'Off Air';
                    break;
                default:
                    $location = "Unknown Source";
                    foreach ($streams as $stream) {
                        if ($stream['camera'] == $response['camera']) {
                            $location = $stream["streamname"];
                            break;
                        }
                    }
            }

            return [
                'camera' => $response['camera'],
                'location' => $location,
            ];
        } else {
            return [
                'camera' => -1,
                'location' => null,
            ];
        }
    }

    /**
     * [setWebcam description].
     *
     * @param [type] $id [description]
     */
    public static function setWebcam($id)
    {
        $validCams = ['studio1', 'studio2', 'cam1', 'cam2', 'cam5', 'hall', 'office'];
        if (in_array($id, $validCams)) {
            $ch = \curl_init(Config::$webcam_set_url.$id);
            \curl_setopt($ch, CURLOPT_POST, true);
            \curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            \curl_exec($ch); // ignore response
        }
    }
}
